<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Pagination\PaginatedQueryCreator;

class PaginatedReadMembersQueryCreator extends PaginatedQueryCreator
{
    public function createConnection()
    {
        return Connection::create('paginatedReadMembers')
            ->setConnectionType($this->manager->getType('member'))
            ->setArgs([
                'Email' => [
                    'type' => Type::string()
                ]
            ])
            ->setSortableFields(['ID', 'FirstName', 'Email'])
            ->setConnectionResolver(function ($obj, $args, $context) {
                $member = Member::singleton();
                if (!$member->canView($context['currentUser'])) {
                    throw new \InvalidArgumentException(sprintf(
                        '%s view access not permitted',
                        Member::class
                    ));
                }
                $list = Member::get();

                // Optional filtering by properties
                if (isset($args['Email'])) {
                    $list = $list->filter('Email', $args['Email']);
                }

                return $list;
            });
    }
}
