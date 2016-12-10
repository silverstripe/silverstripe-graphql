<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\TypeCreator;
use SilverStripe\GraphQL\Pagination\Connection;

class MemberTypeCreator extends TypeCreator
{

    public function attributes()
    {
        return [
            'name' => 'member'
        ];
    }

    public function fields()
    {
        $groups = Connection::create([
            'name' => 'Groups',
            'nodeType' => $this->manager->getType('group'),
            'description' => 'A list of the users groups',
        ]);

        return [
            'ID' => ['type' => Type::nonNull(Type::id())],
            'Email' => ['type' => Type::string()],
            'FirstName' => ['type' => Type::string()],
            'Surname' => ['type' => Type::string()],
            'Groups' => [
                'type' => $groups->toType(),
                'args' => $groups->args(),
                'resolve' => function($obj, $args) {
                    return Connection::prepareList($obj->Groups(), $args);
                }
            ]
        ];
    }

}
