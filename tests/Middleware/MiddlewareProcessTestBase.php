<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;
use SilverStripe\GraphQL\Schema\Schema as SchemaSchema;

abstract class MiddlewareProcessTestBase extends SapphireTest
{
    /**
     * @var callable
     */
    protected $defaultCallback;

    protected function setUp(): void
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
        $vars = [],
        $cb = null
    ) {
        if (!$cb) {
            $cb = $this->defaultCallback;
        }
        return $middleware->process($this->createFakeSchema(), $query, $context, $vars, $cb);
    }

    protected function createFakeSchema()
    {
        return new Schema([
            'query' => new ObjectType([
                'name' => 'test',
                'fields' => [['type' => Type::string(), 'name' => 'test']]
            ])
        ]);
    }
}
