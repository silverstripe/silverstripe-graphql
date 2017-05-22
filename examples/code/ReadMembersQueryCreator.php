<?php

namespace MyProject\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\QueryCreator;

class ReadMembersQueryCreator extends QueryCreator implements OperationResolver
{
    public function attributes()
    {
        return [
            'name' => 'readMembers'
        ];
    }

    public function args()
    {
        return [
            'Email' => ['type' => Type::string()]
        ];
    }

    public function type()
    {
        return Type::listOf($this->manager->getType('member'));
    }

    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
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
    }
}
