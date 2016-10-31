<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\QueryCreator;
use MyProject\MyDataObject;
use SilverStripe\Security\Member;

class ReadMembersQueryCreator extends QueryCreator
{

    public function attributes()
    {
        return [
            'name' => 'readMembers'
        ];
    }

    public function args() {
        return [
            'Email' => ['type' => Type::string()]
        ];
    }

    public function type()
    {
        // Return a "thunk" to lazy load types
        return function() {
            return Type::listOf($this->manager->getType('member'));
        };
    }


    public function resolve($args)
    {
        $list = Member::get();

        // Optional filtering by properties
        if(isset($args['Email'])) {
            $list = $list->filter('Email', $args['Email']);
        }

        return $list;
    }
}