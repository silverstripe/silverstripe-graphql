<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Scaffolding;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Dev\SapphireTest;
use InvalidArgumentException;
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\InheritanceScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;

class InheritanceScaffolderTest extends SapphireTest
{
    public function testThrowsOnNonExistentClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/not exist/');

        $scaffolder = new InheritanceScaffolder('fail');
    }

    public function testThrowsOnNonDataObjectClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/subclass of/');

        $scaffolder = new InheritanceScaffolder(Controller::class);
    }

    public function testGettersAndSetters()
    {
        $scaffolder = new InheritanceScaffolder(DataObjectFake::class);
        $this->assertEquals(DataObjectFake::class, $scaffolder->getRootClass());
        $scaffolder->setRootClass(FakePage::class);
        $this->assertEquals(FakePage::class, $scaffolder->getRootClass());
        $scaffolder->setSuffix('test');
        $this->assertEquals('test', $scaffolder->getSuffix());
    }

    public function testScaffolding()
    {
        $schema = StaticSchema::inst();
        $manager = new Manager();
        $manager->addType(new ObjectType([
            'name' => $schema->typeNameForDataObject(FakeSiteTree::class)
        ]));
        $manager->addType(new ObjectType([
            'name' => $schema->typeNameForDataObject(FakePage::class)
        ]));
        $manager->addType(new ObjectType([
            'name' => $schema->typeNameForDataObject(FakeRedirectorPage::class)
        ]));

        $scaffolder = new InheritanceScaffolder(FakeSiteTree::class, 'TheSuffix');
        $scaffold = $scaffolder->scaffold($manager);
        $typeName = StaticSchema::inst()->typeNameForDataObject(FakeSiteTree::class);
        $this->assertEquals($typeName . 'TheSuffix', $scaffold->config['name']);

        $nestedTypes = array_map(function ($type) {
            return $type->config['name'];
        }, $scaffold->getTypes());

        $this->assertContains(
            StaticSchema::inst()->typeNameForDataObject(FakeSiteTree::class),
            $nestedTypes
        );
    }
}
