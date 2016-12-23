<?php

namespace SilverStripe\GraphQL\Pagination;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Type\Definition\EnumType;

class SortInputType
{
    use Injectable;

    /**
     * @var InputObjectType
     */
    private $type;

    /**
     * @var string
     */
    private $inputName;

    /**
     * @var array Keyed by field argument name, values as DataObject column names.
     *            Does not support in-memory sorting for composite values (getters)
     */
    protected $sortableFields = [];

    public function __construct($name)
    {
        $this->inputName = $name;
    }

    /**
     * @param array $sortableFields
     *
     * @return $this
     */
    public function setSortableFields($sortableFields)
    {
        $this->sortableFields = $sortableFields;

        return $this;
    }

    /**
     * @return Type
     */
    public function toType()
    {
        $values = [];

        foreach ($this->sortableFields as $fieldAlias => $fieldName) {
            $values[$fieldAlias] = [
                'value' => $fieldAlias,
            ];
        }

        $sortableField = new EnumType([
            'name' => ucfirst($this->inputName).'SortFieldType',
            'description' => 'Field name to sort by.',
            'values' => $values,
        ]);

        if (!$this->type) {
            $this->type = new InputObjectType([
                'name' => ucfirst($this->inputName).'SortInputType',
                'description' => 'Define the sorting',
                'fields' => [
                    'field' => [
                        'type' => Type::nonNull($sortableField),
                        'description' => 'Sort field name.',
                    ],
                    'direction' => [
                        'type' => Injector::inst()->get(SortDirectionType::class)->toType(),
                        'description' => 'Sort direction (ASC / DESC)',
                    ],
                ],
            ]);
        }

        return $this->type;
    }
}
