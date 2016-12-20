<?php

namespace SilverStripe\GraphQL\Pagination;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Core\Injector\Injectable;

class SortDirectionType
{
    use Injectable;

    /**
     * @var ObjectType
     */
    private $type;

    /**
     * @return ObjectType
     */
    public function toType()
    {
        if (!$this->type) {
            $this->type = new EnumType([
                'name' => 'SortDirection',
                'description' => 'Set order order to either ASC or DESC',
                'values' => [
                    'ASC' => [
                        'value' => 'ASC',
                        'description' => 'Lowest value to highest.'
                    ],
                    'DESC' => [
                        'value' => 'DESC',
                        'description' => 'Highest value to lowest.'
                    ]
                ]
            ]);
        }

        return $this->type;
    }
}
