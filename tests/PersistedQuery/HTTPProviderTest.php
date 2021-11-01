<?php

namespace SilverStripe\GraphQL\Tests\PersistedQuery;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\PersistedQuery\HTTPClient;
use SilverStripe\GraphQL\PersistedQuery\HTTPProvider;

class HTTPProviderTest extends SapphireTest
{
    public function testURLValidation()
    {
        /* @var HTTPProvider $provider */
        $provider = Injector::inst()->create(HTTPProvider::class);
        $this->expectException(\InvalidArgumentException::class);
        $provider->setSchemaMapping(['default' => 'not a url']);
    }

    public function testSchemaMapping()
    {
        /* @var HTTPProvider $provider */
        $provider = Injector::inst()->create(HTTPProvider::class);
        $provider->setSchemaMapping([
            'default' => 'http://example.com'
        ]);

        $mapping = $provider->getSchemaMapping();
        $this->assertEquals('http://example.com', $mapping['default']);
    }

    public function testHTTPRequests()
    {
        $mock = $this->getMockBuilder(HTTPClient::class)
            ->setMethods(['getURL'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getURL')
            ->with(
                $this->equalTo('http://example.com/foo'),
                $this->equalTo(HTTPProvider::config()->timeout)
            )
            ->willReturn('{"someID": "someQuery"}');

        $provider = new HTTPProvider($mock);

        $provider->setSchemaMapping([
            'default' => 'http://example.com/foo',
        ]);

        $mapping = $provider->getQueryMapping('default');
        $this->assertNotNull($mapping);
        $this->assertTrue(is_array($mapping));
        $this->assertArrayHasKey('someID', $mapping);
        $this->assertEquals('someQuery', $mapping['someID']);
        $this->assertEquals('someQuery', $provider->getByID('someID', 'default'));
    }
}
