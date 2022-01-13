<?php


namespace Schema\BulkLoader;

use Fake\FakeBulkLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\BulkLoader\NamespaceLoader;
use SilverStripe\GraphQL\Schema\BulkLoader\RegistryBackend;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A;

class RegistryBackendTest extends SapphireTest
{
    public function testRegistryBackend()
    {
        $backend = new RegistryBackend(
            new FakeBulkLoader(),
            new NamespaceLoader()
        );

        $this->assertInstanceOf(FakeBulkLoader::class, $backend->getByID('fake'));
        $this->assertInstanceOf(NamespaceLoader::class, $backend->getByID('namespaceLoader'));
        $this->assertNull($backend->getByID('fail'));
    }

    public function testExeception()
    {
        $this->expectException('InvalidArgumentException');
        new RegistryBackend(
            new A()
        );
    }
}
