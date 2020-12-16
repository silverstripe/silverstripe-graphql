<?php

 /** GENERATED CODE -- DO NOT MODIFY **/

namespace SSGraphQLSchema_4168bad56a811e627a58612469fe9a63;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\GraphQL\Schema\Resolver\ComposedResolver;
class MyType extends ObjectType{
    public function __construct()
    {
        parent::__construct([
            'name' => 'MyType',
                'fields' => function () {
                return [
                                    [
                        'name' => 'field1',
                        'type' => Types::String(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                                            ],
                                    [
                        'name' => 'field2',
                        'type' => Types::Boolean(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                                            ],
                                ];
            },
        ]);
    }
}
