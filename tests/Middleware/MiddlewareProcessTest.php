<?php
namespace SilverStripe\GraphQL\Tests\Middleware;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;
use SilverStripe\GraphQL\Schema\Components\Schema;
use SilverStripe\GraphQL\Tests\Fake\TypeRegistryFake;

abstract class MiddlewareProcessTest extends SapphireTest
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
        return new Schema(new TypeRegistryFake());
    }
}
