<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\ModelFieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataObject;

/**
 * Defines a permission checking plugin for queries. Subclasses just need to
 * provide a resolver function
 */
abstract class AbstractCanViewPermission implements FieldPlugin
{
    /**
     * @param Field $field
     * @param Schema $schema
     * @param array $config
     */
    public function apply(Field $field, Schema $schema, array $config = []): void
    {
        $field->addResolverAfterware(
            $this->getPermissionResolver()
        );
    }

    abstract protected function getPermissionResolver(): callable;
}
