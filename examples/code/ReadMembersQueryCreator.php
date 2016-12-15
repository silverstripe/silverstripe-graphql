<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\QueryCreator;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Pagination\PaginatedQueryCreator;
use SilverStripe\GraphQL\Manager;

class ReadMembersQueryCreator extends PaginatedQueryCreator
{
    public function connection() {
        return Connection::create('readMembers')
            ->setConnectionType(function() {
                return $this->manager->getType('member');
            })
            ->setArgs([
                'Email' => [
                    'type' => Type::string()
                ]
            ])
            ->setSortableFields(['ID', 'FirstName', 'Email'])
            ->setConnectionResolver(function($obj, $args) {
                $list = Member::get();

                // Optional filtering by properties
                if(isset($args['Email'])) {
                    $list = $list->filter('Email', $args['Email']);
                }

                return $list;
            });
    }
}
