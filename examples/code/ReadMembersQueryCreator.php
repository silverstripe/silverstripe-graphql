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
        return Connection::create([
            'name' => 'readMembers',
            'args' => [
                'Email' => ['type' => Type::string()]
            ],
            'nodeType' => $this->manager->getType('member'),
            'nodeResolve' => function() {
                $list = Member::get();

                // Optional filtering by properties
                if(isset($args['Email'])) {
                    $list = $list->filter('Email', $args['Email']);
                }

                return $list;
            }
        ]);
    }
}
