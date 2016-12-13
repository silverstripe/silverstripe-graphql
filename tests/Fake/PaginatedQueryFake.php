<?php


namespace SilverStripe\GraphQL\Tests\Fake;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\QueryCreator;
use SilverStripe\GraphQL\Pagination\PaginatedQueryCreator;
use SilverStripe\GraphQL\Pagination\Connection;

class PaginatedQueryFake extends PaginatedQueryCreator
{
    public function connection()
    {
        return Connection::create([
            'name' => 'testPagination',
            'args' => [
                'MyField' => ['type' => Type::string()]
            ],
            'nodeType' => $this->manager->getType('TypeCreatorFake'),
            'nodeResolve' => function() {
                return $list;
            }
        ]);
    }
}
