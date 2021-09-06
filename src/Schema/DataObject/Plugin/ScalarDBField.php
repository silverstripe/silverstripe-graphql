<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\ModelTypePlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Ensures any field that ends up as ViewableData, e.g. DBField,
 * invokes forTemplate() after resolving.
 */
class ScalarDBField implements ModelTypePlugin
{
    const IDENTIFIER = 'scalarDBField';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param ModelType $type
     * @param Schema $schema
     * @param array $config
     */
    public function apply(ModelType $type, Schema $schema, array $config = []): void
    {
        foreach ($type->getFields() as $field) {
            if (!$field instanceof ModelField || !$field->getModel() instanceof DataObjectModel) {
                continue;
            }
            if (!$field->isList()) {
                $field->addResolverAfterware([static::class, 'resolve']);
            }
        }
    }

    /**
     * @param $obj
     * @return mixed
     */
    public static function resolve($obj)
    {
        if ($obj instanceof DBField) {
            return $obj->getValue();
        }

        return $obj;
    }
}
