<?php

namespace SilverStripe\GraphQL\Pagination;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\Core\Injector\Injector;
use GraphQL\Type\Definition\EnumType;
use SilverStripe\GraphQL\TypeCreator;

/**
 * Type creator for an enum value for a list of possible sortable fields
 *
 * @see SortDirectionTypeCreator
 */
class SortInputTypeCreator extends TypeCreator
{
    /**
     * @var InputObjectType
     */
    protected $type;

    /**
     * @var string
     */
    protected $inputName;

    /**
     * @var array Keyed by field argument name, values as DataObject column names.
     * Does not support in-memory sorting for composite values (getters).
     */
    protected $sortableFields = [];

    protected $inputObject = true;

    /**
     * Build a sort input creator with a given name prefix.
     *
     * @param string $name Prefix for this input type name.
     */
    public function __construct($name)
    {
        parent::__construct();
        $this->inputName = $name;
    }

    /**
     * Specify the list of sortable fields
     *
     * @param array $sortableFields
     * @return $this
     */
    public function setSortableFields($sortableFields)
    {
        $this->sortableFields = $sortableFields;
        return $this;
    }

    public function toType()
    {
        if (!$this->type) {
            $this->type = parent::toType();
        }
        return $this->type;
    }

    public function getAttributes()
    {
        // Don't wrap static fields in callback
        return array_merge(
            $this->attributes(),
            [
                'fields' => function () {
                    return $this->fields();
                }
            ]
        );
    }

    public function attributes()
    {
        return [
            'name' => ucfirst($this->inputName) .'SortInputType',
            'description' => 'Define the sorting',
        ];
    }

    public function fields()
    {
        $values = [];
        foreach ($this->sortableFields as $fieldAlias => $fieldName) {
            $values[$fieldAlias] = [
                'value' => $fieldAlias
            ];
        }

        $sortableField = new EnumType([
            'name' => ucfirst($this->inputName) . 'SortFieldType',
            'description' => 'Field name to sort by.',
            'values' => $values,
        ]);

        return [
            'field' => [
                'type' => Type::nonNull($sortableField),
                'description' => 'Sort field name.',
            ],
            'direction' => [
                'type' => Injector::inst()->get(SortDirectionTypeCreator::class)->toType(),
                'description' => 'Sort direction (ASC / DESC)',
            ]
        ];
    }
}
