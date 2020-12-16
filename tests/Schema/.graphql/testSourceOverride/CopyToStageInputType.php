<?php

 /** GENERATED CODE -- DO NOT MODIFY **/

namespace SSGraphQLSchema_4168bad56a811e627a58612469fe9a63;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\GraphQL\Schema\Resolver\ComposedResolver;
class CopyToStageInputType extends InputObjectType{
    public function __construct()
    {
        parent::__construct([
            'name' => 'CopyToStageInputType',
                'fields' => function () {
                return [
                                    [
                        'name' => 'id',
                        'type' => Types::nonNull(Types::ID()),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                            'description' => 'The ID of the record to copy',
                                                            ],
                                    [
                        'name' => 'fromVersion',
                        'type' => Types::Int(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                            'description' => 'The source version number to copy',
                                                            ],
                                    [
                        'name' => 'fromStage',
                        'type' => Types::VersionedStage(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                            'description' => 'The source stage to copy',
                                                            ],
                                    [
                        'name' => 'toStage',
                        'type' => Types::VersionedStage(),
                        'resolve' =>     ['SilverStripe\GraphQL\Schema\Resolver\DefaultResolver', 'defaultFieldResolver'],
                                            'description' => 'The destination state to copy to',
                                                            ],
                                ];
            },
        ]);
    }
}
