<?php

namespace SilverStripe\GraphQL\Tests\PersistedQuery;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\PersistedQuery\FileProvider;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;

class FileProviderTest extends SapphireTest
{
    public function testSchemaMapping()
    {
        /* @var PersistedQueryMappingProvider $provider */
        $provider = new FileProvider();
        $provider->setSchemaMapping([
            'default' => $this->getFilePath(),
            'nothing' => '/fail/path',
        ]);

        $mapping = $provider->getSchemaMapping();
        $this->assertEquals($this->getFilePath(), $mapping['default']);
        $this->assertEquals('/fail/path', $mapping['nothing']);
    }

    public function testGetByID()
    {
        /* @var PersistedQueryMappingProvider $provider */
        $provider = new FileProvider();
        $provider->setSchemaMapping([
            'default' => $this->getFilePath()
        ]);

        $this->assertEquals(
            'query{validateToken{Valid Message Code}}',
            $provider->getByID('592159a6-aa3a-411a-9ca6-fa3912fa929a', 'default')
        );

        $this->assertNull(
            $provider->getByID('fail', 'default')
        );
    }

    protected function getFilePath()
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [
                __DIR__,
                'Fixture',
                'persisted_query_mapping.json'
            ]
        );
    }
}
