<?php

 /** GENERATED CODE -- DO NOT MODIFY **/

namespace SSGraphQLSchema_4168bad56a811e627a58612469fe9a63;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\GraphQL\Schema\Resolver\ComposedResolver;
class Query extends ObjectType{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Query',
                'fields' => function () {
                return [
                                    [
                        'name' => 'readMyTypes',
                        'type' => Types::listOf(Types::MyType()),
                        'resolve' =>     ['SilverStripe\GraphQL\Tests\Fake\IntegrationTestResolverA', 'resolveReadMyTypes'],
                                                            ],
                                    [
                        'name' => 'readMyTypesAgain',
                        'type' => Types::listOf(Types::MyType()),
                        'resolve' =>     ['SilverStripe\GraphQL\Tests\Fake\IntegrationTestResolverA', 'resolveReadMyTypesAgain'],
                                                            ],
                                ];
            },
        ]);
    }
}
