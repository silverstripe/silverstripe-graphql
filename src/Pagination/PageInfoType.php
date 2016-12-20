<?php

namespace SilverStripe\GraphQL\Pagination;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Core\Injector\Injectable;

/**
 * Supports offset based pagination within GraphQL.
 */
class PageInfoType
{
    use Injectable;

    /**
     * @var ObjectType
     */
    protected $type;

    public function toType()
    {
        if (!$this->type) {
            $this->type = new ObjectType([
                'name' => 'PageInfo',
                'description' => 'Information about pagination in a connection.',
                'fields' => [
                    'totalCount' => [
                        'type' => Type::nonNull(Type::int())
                    ],
                    'hasNextPage' => [
                        'type' => Type::nonNull(Type::boolean())
                    ],
                    'hasPreviousPage' => [
                        'type' => Type::nonNull(Type::boolean())
                    ]
                ]
            ]);
        }

        return $this->type;
    }
}
