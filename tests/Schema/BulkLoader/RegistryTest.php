<?php


namespace Schema\BulkLoader;

use Fake\FakeBulkLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\BulkLoader\Registry;

class RegistryTest extends SapphireTest
{
    public function testRegistry()
    {
        $inst = Registry::inst();
        $inst2 = Registry::inst();
        $this->assertSame($inst, $inst2);

        $this->assertInstanceOf(FakeBulkLoader::class, $inst->getByID('fake'));
    }
}
