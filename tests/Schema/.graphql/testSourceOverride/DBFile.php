<?php

 /** GENERATED CODE -- DO NOT MODIFY **/

namespace SSGraphQLSchema_4168bad56a811e627a58612469fe9a63;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\GraphQL\Schema\Resolver\ComposedResolver;
class DBFile extends ObjectType{
    public function __construct()
    {
        parent::__construct([
            'name' => 'DBFile',
                'fields' => function () {
                return [
                                    [
                        'name' => 'filename',
                        'type' => Types::String(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                                            ],
                                    [
                        'name' => 'hash',
                        'type' => Types::String(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                                            ],
                                    [
                        'name' => 'variant',
                        'type' => Types::String(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                                            ],
                                    [
                        'name' => 'url',
                        'type' => Types::String(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                                            ],
                                ];
            },
        ]);
    }
}
