<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use Exception;
use SilverStripe\GraphQL\Middleware\HTTPMethodMiddleware;

class HTTPMethodMiddlewareTest extends MiddlewareProcessTestBase
{
    public function testItDoesntDoAnythingIfNotAMutation()
    {
        $this->assertEquals('resolved', $this->simulateMiddlewareProcess(
            new HTTPMethodMiddleware(),
            'query testQuery { foo }',
            ['httpMethod' => 'GET']
        ));
        $this->assertEquals('resolved', $this->simulateMiddlewareProcess(
            new HTTPMethodMiddleware(),
            ' not a valid graphql query and no one cares',
            ['httpMethod' => 'POST']
        ));
    }

    public function testItThrowsIfNotPOSTorGET()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/must be POST or GET/');
        $result = $this->simulateMiddlewareProcess(
            new HTTPMethodMiddleware(),
            ' query someQuery { tester }',
            ['httpMethod' => 'DELETE']
        );
        $this->assertNotEquals('resolved', $result);
    }

    public function testItThrowsIfMutationIsNotPOST()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Mutations must use the POST/');
        $result = $this->simulateMiddlewareProcess(
            new HTTPMethodMiddleware(),
            ' mutation someMutation { tester }',
            ['httpMethod' => 'GET']
        );
        $this->assertNotEquals('resolved', $result);
    }

    public function testItResolvesIfMutationIsPOST()
    {
        $result = $this->simulateMiddlewareProcess(
            new HTTPMethodMiddleware(),
            ' mutation someMutation { tester }',
            ['httpMethod' => 'POST']
        );
        $this->assertEquals('resolved', $result);
    }
}
