<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\Core\Object;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

/**
 * Supports offset based pagination within GraphQL
 *
 * @todo cursor support
 */
class PageInfoType extends Object {

    private $type;

    public function toType() {
        if(!$this->type) {
            $this->type = new ObjectType([
                'name' => 'PageInfo',
                'description' => 'Information about pagination in a connection.',
                'fields' => [
                    'totalCount' => ['type' => Type::nonNull(Type::id())],
                    'hasNextPage' => ['type' => Type::nonNull(Type::boolean())],
                    'hasPreviousPage' => ['type' => Type::nonNull(Type::boolean())]
                ]
            ]);
        }

        return $this->type;
    }
}
