<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\Core\Object;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\Core\Injector\Injector;

class SortInputType extends Object {

    /**
     * @var InputObjectType
     */
    private $type;

    /**
     * @return ObjectType
     */
    public function toType() {
        if(!$this->type) {
            $this->type = new InputObjectType([
                'name' => 'sortBy',
                'description' => 'Define the sorting',
                'fields' => [
                    'field' => [
                        'type' => Type::nonNull(Type::string()),
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
