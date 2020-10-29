<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceChain;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\ModelTypePlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataObject;

/**
 * Ensures DataObject models merge their plugins with ancestors
 */
class InheritedPlugins implements ModelTypePlugin
{
    const IDENTIFIER = 'inheritedPlugins';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param ModelType $type
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(ModelType $type, Schema $schema, array $config = []): void
    {
        $sourceClass = $type->getModel()->getSourceClass();
        Schema::invariant(
            is_subclass_of($sourceClass, DataObject::class),
            '%s only applies to %s subclasses',
            static::class,
            DataObject::class
        );

        $chain = InheritanceChain::create($sourceClass);
        if (!$chain->hasAncestors()) {
            return;
        }
        $ancestors = array_reverse($chain->getAncestralModels());
        /* @var ModelType[] $ancestorModels */
        $ancestorModels = [];
        foreach ($ancestors as $ancestor) {
            $modelType = $schema->getModelByClassName($ancestor);
            if (!$modelType) {
                continue;
            }
            $ancestorModels[] = $modelType;
        }

        $pluginArgs = [];
        foreach ($ancestorModels as $model) {
            $pluginArgs[] = $model->getPlugins(false);
        }
        $pluginArgs[] = $type->getPlugins(false);

        $plugins = array_replace_recursive(...$pluginArgs);
        $type->setPlugins($plugins);

        $operations = $type->getOperations();

        /* @var ModelField $operation */
        foreach ($operations as $name => $operation) {
            $pluginArgs = [];
            foreach ($ancestorModels as $model) {
                $ancestorOperation = $model->getOperations()[$name] ?? null;
                if ($ancestorOperation) {
                    $pluginArgs[] = $ancestorOperation->getPlugins(false);
                }
            }
            $pluginArgs[] = $operation->getPlugins(false);
            $plugins = array_replace_recursive(...$pluginArgs);
            $operation->setPlugins($plugins);
        }
    }
}
