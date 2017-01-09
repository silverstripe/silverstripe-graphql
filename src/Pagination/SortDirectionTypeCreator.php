<?php

namespace SilverStripe\GraphQL\Pagination;

use GraphQL\Type\Definition\EnumType;
use SilverStripe\GraphQL\TypeCreator;

/**
 * Type for specifying the sort direction for a specific field.
 *
 * @see SortInputTypeCreator
 */
class SortDirectionTypeCreator extends TypeCreator
{
    /**
     * @var EnumType
     */
    protected $type;

    protected $inputObject = true;

    public function toType()
    {
        if (!$this->type) {
            $this->type = new EnumType($this->toArray());
        }
        return $this->type;
    }

    public function getAttributes()
    {
        return $this->attributes();
    }

    public function attributes()
    {
        return [
            'name' => 'SortDirection',
            'description' => 'Set order order to either ASC or DESC',
            'values' => [
                'ASC' => [
                    'value' => 'ASC',
                    'description' => 'Lowest value to highest.',
                ],
                'DESC' => [
                    'value' => 'DESC',
                    'description' => 'Highest value to lowest.',
                ],
            ],
        ];
    }
}
