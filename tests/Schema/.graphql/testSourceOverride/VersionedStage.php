<?php

 /** GENERATED CODE -- DO NOT MODIFY **/

namespace SSGraphQLSchema_4168bad56a811e627a58612469fe9a63;
use GraphQL\Type\Definition\EnumType;
class VersionedStage extends EnumType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'VersionedStage',
            'values' => [
                        'DRAFT' => [
                    'value' => 'Stage',
                                    'description' => 'The draft stage',
                                ],
                        'LIVE' => [
                    'value' => 'Live',
                                    'description' => 'The live stage',
                                ],
                    ],
                    'description' => 'The stage to read from or write to',
                ]);
    }
}
