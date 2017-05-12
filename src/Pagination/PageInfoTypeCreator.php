<?php

namespace SilverStripe\GraphQL\Pagination;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\GraphQL\TypeCreator;

/**
 * Supports offset based pagination within GraphQL.
 */
class PageInfoTypeCreator extends TypeCreator
{
    /**
     * Cached type
     *
     * @var ObjectType
     */
    protected $type;

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
            'name' => 'PageInfo',
            'description' => 'Information about pagination in a connection.',
        ];
    }

    public function fields()
    {
        return [
            'totalCount' => [
                'type' => Type::nonNull(Type::int())
            ],
            'hasNextPage' => [
                'type' => Type::nonNull(Type::boolean())
            ],
            'hasPreviousPage' => [
                'type' => Type::nonNull(Type::boolean())
            ],
        ];
    }
}
