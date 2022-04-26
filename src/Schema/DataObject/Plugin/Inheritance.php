<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceBuilder;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceUnionBuilder;
use SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ModelTypePlugin;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataObject;
use ReflectionException;

/**
 * Adds inheritance fields to a DataObject type, and exposes its ancestry
 */
class Inheritance implements PluginInterface, SchemaUpdater, ModelTypePlugin
{

    const IDENTIFIER = 'inheritance';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @throws SchemaBuilderException
     */
    public static function updateSchema(Schema $schema): void
    {
        InterfaceBuilder::create($schema)
            ->applyBaseInterface();
    }

    /**
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    public function apply(ModelType $type, Schema $schema, array $config = []): void
    {
        if (!$type->getModel() instanceof DataObjectModel) {
            return;
        }
        $useUnions = $config['useUnionQueries'] ?? false;
        $hideAncestors = $config['hideAncestors'] ?? [];

        if (in_array($type->getModel()->getSourceClass(), $hideAncestors ?? [])) {
            return;
        }

        $inheritance = InheritanceBuilder::create($schema, $hideAncestors);
        $interfaces = InterfaceBuilder::create($schema, $hideAncestors);

        $class = $type->getModel()->getSourceClass();
        if ($inheritance->isLeafModel($class)) {
            $inheritance->fillAncestry($type);
        } elseif ($inheritance->isBaseModel($class)) {
            $inheritance->fillDescendants($type);
            $interfaces->createInterfaces($type);
            if ($useUnions) {
                InheritanceUnionBuilder::create($schema)
                    ->createUnions($type)
                    ->applyUnionsToQueries($type);
            } else {
                $interfaces->applyInterfacesToQueries($type);
            }
        }
    }
}
