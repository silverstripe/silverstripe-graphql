<?php

 /** GENERATED CODE -- DO NOT MODIFY **/

namespace SSGraphQLSchema_4168bad56a811e627a58612469fe9a63;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\GraphQL\Schema\Resolver\ComposedResolver;
class VersionedInputType extends InputObjectType{
    public function __construct()
    {
        parent::__construct([
            'name' => 'VersionedInputType',
                'fields' => function () {
                return [
                                    [
                        'name' => 'mode',
                        'type' => Types::VersionedQueryMode(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                                            ],
                                    [
                        'name' => 'archiveDate',
                        'type' => Types::String(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                            'description' => 'The date to use for archive',
                                                            ],
                                    [
                        'name' => 'status',
                        'type' => Types::listOf(Types::VersionedStatus()),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                            'description' => 'If mode is STATUS, specify which versioned statuses',
                                                            ],
                                    [
                        'name' => 'version',
                        'type' => Types::Int(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                                            ],
                                ];
            },
        ]);
    }
}
