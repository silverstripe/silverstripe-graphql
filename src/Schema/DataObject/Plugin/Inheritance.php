<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\DataObject\InheritanceBuilder;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceUnionBuilder;
use SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataObject;
use ReflectionException;

/**
 * Adds inheritance fields to a DataObject type, and exposes its ancestry
 */
class Inheritance implements PluginInterface, SchemaUpdater
{

    const IDENTIFIER = 'inheritance';

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
        $baseModels = [];
        $leafModels = [];

        $inheritance = InheritanceBuilder::create($schema);
        $interfaces = InterfaceBuilder::create($schema);
        $unions = InheritanceUnionBuilder::create($schema);

        foreach ($schema->getModelTypesFromClass(DataObject::class) as $modelType) {
            $class = $modelType->getModel()->getSourceClass();
            if ($inheritance->isBaseModel($class)) {
                $baseModels[] = $modelType;
            } else if ($inheritance->isLeafModel($class)) {
                $leafModels[] = $modelType;
            }
        }

        foreach ($leafModels as $modelType) {
            $inheritance->fillAncestry($modelType);
        }

        foreach ($baseModels as $modelType) {
            $inheritance->fillDescendants($modelType);
            $interfaces->createInterfaces($modelType);
        }

        $interfaces->applyBaseInterface();

        $unions->createUnions();
        $unions->applyUnions();
    }

}
