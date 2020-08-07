<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Dev\Benchmark;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceChain;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataObject;
use ReflectionException;

class Inheritance implements PluginInterface, SchemaUpdater
{
    const IDENTIFIER = 'inheritance';

    /**
     * @var array
     */
    private static $touchedNodes = [];

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param Schema $schema
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    public static function updateSchema(Schema $schema): void
    {
        Benchmark::start('inheritance');
        foreach ($schema->getModels() as $modelType) {
            $class = $modelType->getModel()->getSourceClass();
            if (!is_subclass_of($class, DataObject::class)) {
                continue;
            }
            $baseClass = InheritanceChain::create($class)->getBaseClass();
            if (self::isTouched($baseClass)) {
                continue;
            }
            self::addInheritance($schema, $baseClass);
            self::touchNode($baseClass);
        }
        Benchmark::end('inheritance');
    }

    /**
     * @param Schema $schema
     * @param string $class
     * @param ModelType|null $parentModel
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    private static function addInheritance(Schema $schema, string $class, ?ModelType $parentModel = null)
    {
        $inheritance = InheritanceChain::create($class);
        $modelType = $schema->findOrMakeModel($class);

        if ($parentModel) {
            // Children should get at least their parents' exposed fields
            $modelType->mergeWith($parentModel);
        }

        if (!$inheritance->hasDescendants()) {
            return;
        }

        // Add the new __extends field
        $extendsType = $inheritance->getExtensionType();
        $schema->addType($extendsType);
        $modelType->addField(InheritanceChain::getName(), $extendsType->getName());
        foreach ($inheritance->getDirectDescendants() as $descendantClass) {
            self::addInheritance($schema, $descendantClass, $modelType);
        }
    }

    /**
     * @param string $baseClass
     */
    private static function touchNode(string $baseClass): void
    {
        self::$touchedNodes[$baseClass] = true;
    }

    /**
     * @param string $baseClass
     * @return bool
     */
    private static function isTouched(string $baseClass): bool
    {
        return isset(self::$touchedNodes[$baseClass]);
    }

}
