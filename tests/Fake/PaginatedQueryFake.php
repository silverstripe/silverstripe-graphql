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
        return Connection::create('testPagination')
            ->setArgs([
                'MyField' => [
                    'type' => Type::string()
                ]
            ])
            ->setConnectionType(function()  {
                return $this->manager->getType('TypeCreatorFake');
            })
            ->setConnectionResolver(function() {
                $list = DataObjectFake::get();

                return $list;
            });
    }
}
