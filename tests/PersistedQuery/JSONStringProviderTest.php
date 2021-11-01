<?php

namespace SilverStripe\GraphQL\Tests\PersistedQuery;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\PersistedQuery\JSONStringProvider;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;

class JSONStringProviderTest extends SapphireTest
{
    public function testJSONValidation()
    {
        /* @var PersistedQueryMappingProvider $provider */
        $provider = new JSONStringProvider();
        $this->expectException(\InvalidArgumentException::class);
        $provider->setSchemaMapping(['default' => 'not a JSON string']);
    }

    public function testSchemaMapping()
    {
        /* @var PersistedQueryMappingProvider $provider */
        $provider = new JSONStringProvider();
        $provider->setSchemaMapping([
            'default' => '{"someID": "someQuery"}'
        ]);

        $mapping = $provider->getSchemaMapping();
        $this->assertEquals('{"someID": "someQuery"}', $mapping['default']);
    }

    public function testGetByID()
    {
        /* @var PersistedQueryMappingProvider $provider */
        $provider = new JSONStringProvider();
        $provider->setSchemaMapping([
            'default' => '{"someID": "someQuery"}'
        ]);

        $this->assertEquals('someQuery', $provider->getByID('someID', 'default'));
        $this->assertNull($provider->getByID('fail', 'default'));
    }
}
