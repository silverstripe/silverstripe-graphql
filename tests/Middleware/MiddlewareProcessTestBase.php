<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;

abstract class MiddlewareProcessTestBase extends SapphireTest
{
    /**
     * @var callable
     */
    protected $defaultCallback;

    protected function setUp()
    {
        parent::setUp();
        $this->defaultCallback = function () {
            return 'resolved';
        };
    }
    protected function simulateMiddlewareProcess(
        QueryMiddleware $middleware,
        $query,
        $context = [],
        $params = [],
        $cb = null
    ) {
        if (!$cb) {
            $cb = $this->defaultCallback;
        }
        return $middleware->process($this->createFakeSchema(), $query, $context, $params, $cb);
    }

    protected function createFakeSchema()
    {
        return new Schema([
            'query' => new ObjectType([
                'name' => 'test',
                'fields' => [ ['type' => Type::string(), 'name' => 'test'] ]
            ])
        ]);
    }
}
