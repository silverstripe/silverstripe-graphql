<?php


namespace SilverStripe\GraphQL\Tests\Schema\BulkLoader;

use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\BulkLoader\Collection;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B1;

class CollectionTest extends SapphireTest
{
    public function testCollection()
    {
        $collection = new Collection([
            'class1' => 'file1',
            'class2' => 'file2',
            'class3' => 'file3',
            'class4' => 'file4',
        ]);

        $collection->removeClass('class2')
            ->removeFile('file3');

        $this->assertEquals(
            ['class1', 'class4'],
            $collection->getClasses()
        );

        $this->assertEquals(
            ['file1', 'file4'],
            $collection->getFiles()
        );

        $this->assertEquals(
            [
                'class1' => 'file1',
                'class4' => 'file4',
            ],
            $collection->getManifest()
        );
    }

    public function testCreateFromClassList()
    {
        $collection = Collection::createFromClassList([
            A1::class,
            B1::class,
        ]);
        $mod = ModuleLoader::inst()->getManifest()->getModule('silverstripe/graphql');
        $path = $mod->getPath();
        $this->assertEquals(
            [
                A1::class => $path . '/tests/Fake/Inheritance/A1.php',
                B1::class => $path . '/tests/Fake/Inheritance/B1.php',
            ],
            $collection->getManifest()
        );
    }
}
