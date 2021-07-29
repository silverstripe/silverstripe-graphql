<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\ModelTypePlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ViewableData;

/**
 * Ensures any field that ends up as ViewableData, e.g. DBField,
 * invokes forTemplate() after resolving.
 */
class ViewableDataField implements ModelTypePlugin
{
    const IDENTIFIER = 'viewableDataField';

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
            if ($field instanceof ModelField && $field->getModel() instanceof DataObjectModel) {
                $dataClass = $field->getMetadata()->get('dataClass');
                if (is_subclass_of($dataClass, DBField::class)) {
                    $field->addResolverAfterware([static::class, 'resolve']);
                }
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
            return $obj->forTemplate();
        }

        return $obj;
    }
}
