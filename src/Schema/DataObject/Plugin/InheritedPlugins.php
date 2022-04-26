<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Config\MergeStrategy\Priority;
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

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function apply(ModelType $type, Schema $schema, array $config = []): void
    {
        return;
    }
}
