<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;

class QueryMiddlewareTest extends SapphireTest
{
    public function testMiddlewareResponse()
    {
        // Set up a minimal schema that passes validation.
        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'myQuery' => [
                    'type' => Type::string(),
                    'args' => [
                        'name' => Type::string(),
                    ],
                    'resolve' => function ($rootValue, $args) {
                        return 'resolved';
                    }
                ]
            ]
        ]);
        $fakeSchema = new Schema([
            'query' => $queryType
        ]);

        $handler = new QueryHandler();
        $handler->setMiddlewares([
            new DummyResponseMiddleware(),
        ]);

        $this->assertEquals(
            'It was me, Dio!',
            $handler->queryAndReturnResult(
                $fakeSchema,
                'query { myQuery }',
                ['name' => 'Dio']
            )
        );
    }
}
