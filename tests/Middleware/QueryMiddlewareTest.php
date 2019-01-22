<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Schema\SchemaStorageInterface;
use SilverStripe\GraphQL\Tests\Fake\MutationCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;

class QueryMiddlewareTest extends SapphireTest
{
    public function testMiddlewareResponse()
    {
        $config = [
            'types' => [
                'mytype' => TypeCreatorFake::class,
            ],
            'queries' => [
                'myquery' => QueryCreatorFake::class,
            ],
            'mutations' => [
                'mymutation' => MutationCreatorFake::class,
            ],
        ];
        $mock = $this->getMockBuilder(SchemaStorageInterface::class)
            ->getMock();
        $manager = new Manager('test', $mock);
        $manager->applyConfig($config);

        $manager->setMiddlewares([
            new DummyResponseMiddleware(),
        ]);

        $this->assertEquals(
            ['result' => 'It was me, Dio!'],
            $manager->queryAndReturnResult(
                '{ query something }',
                [ 'name' => 'Dio' ]
            )
        );
    }
}
