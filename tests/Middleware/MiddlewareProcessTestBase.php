<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Middleware\QueryMiddlewareInterface;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;

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

    /**
     * @param QueryMiddlewareInterface $middleware
     * @param array|string $operations Array of OperationParams objects, or a string containing a single query/mutation
     * @param ServerConfig|null $config
     * @param null $cb
     * @return ExecutionResult|ExecutionResult[]
     */
    protected function simulateMiddlewareProcess(
        QueryMiddlewareInterface $middleware,
        $operations,
        ServerConfig $config = null,
        $cb = null
    ) {
        if (is_string($operations)) {
            $operations = [
                OperationParams::create(['query' => $operations])
            ];
        }

        if (!$config) {
            $config = $this->createServerConfig();
        }

        if (!$cb) {
            $cb = $this->defaultCallback;
        }

        return $middleware->process($operations, $config, $cb);
    }

    protected function createServerConfig(): ServerConfig
    {
        return QueryHandler::create()->getGraphQLServerConfig($this->createFakeSchema());
    }

    protected function createFakeSchema(): Schema
    {
        return new Schema([
            'query' => new ObjectType([
                'name' => 'test',
                'fields' => [['type' => Type::string(), 'name' => 'test']]
            ])
        ]);
    }
}
