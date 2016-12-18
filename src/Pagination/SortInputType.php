<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\Core\Object;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\Core\Injector\Injector;
use GraphQL\Type\Definition\EnumType;

class SortInputType extends Object
{
    /**
     * @var InputObjectType
     */
    private $type;

    /**
     * @var string
     */
    private $inputName;


    /**
     * @var array
     */
    protected $sortableFields = [];

    /**
     *
     */
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
     * @return ObjectType
     */
    public function toType()
    {
        $values = [];

        foreach($this->sortableFields as $field) {
            $values[$field] = [
                'value' => $field
            ];
        }

        $sortableField  =  new EnumType([
            'name' => $this->inputName . 'SortFieldType',
            'description' => 'Field name to sort by.',
            'values' => $values
        ]);

        if(!$this->type) {
            $this->type = new InputObjectType([
                'name' => $this->inputName .'InputObjectType',
                'description' => 'Define the sorting',
                'fields' => [
                    'field' => [
                        'type' => Type::nonNull($sortableField),
                        'description' => 'Sort field name.'
                    ],
                    'direction' => [
                        'type' => Injector::inst()->get(SortDirectionType::class)->toType(),
                        'description' => 'Sort direction (ASC / DESC)'
                    ]
                ]
            ]);
        }

        return $this->type;
    }
}
