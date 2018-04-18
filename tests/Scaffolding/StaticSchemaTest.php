<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding;

use GraphQL\Type\Definition\ObjectType;
use const Grpc\STATUS_INTERNAL;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use InvalidArgumentException;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;

class StaticSchemaTest extends SapphireTest
{
    public function testTypeNameForDataObject()
    {
        $typeNames = [
            DataObjectFake::class => 'testType',
        ];
        $schema = new StaticSchema();
        $schema->setTypeNames($typeNames);
        $typename = $schema->typeNameForDataObject(DataObjectFake::class);
        $this->assertEquals('testType', $typename);
        $typename = $schema->typeNameForDataObject(FakePage::class);
        $this->assertEquals('SilverStripeFakePage', $typename);

        $typename = $schema->typeNameForDataObject('UnNamespacedClass');
        $this->assertEquals('UnNamespacedClass', $typename);
    }

    public function testEnsureDataObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $schema = new StaticSchema();
        $schema->setTypeNames(['fail' => 'fail']);
    }

    public function testEnsureUnique()
    {
        $this->expectException(InvalidArgumentException::class);
        $typeNames = [
            DataObjectFake::class => 'test1',
            FakePage::class => 'test2',
            FakeRedirectorPage::class => 'test1',
        ];
        $schema = new StaticSchema();
        $schema->setTypeNames($typeNames);
    }

    public function testEnsureAssoc()
    {
        $this->expectException(InvalidArgumentException::class);
        $typeNames = [
            'test1',
            'test2',
        ];
        $schema = new StaticSchema();
        $schema->setTypeNames($typeNames);
    }

    public function testTypeName()
    {
        $schema = new StaticSchema();
        $this->assertEquals('NicelyFormatted_Type', $schema->typeName('Nicely Formatted/Type'));
    }

    public function testIsValidFieldName()
    {
        $schema = new StaticSchema();
        $fake = new DataObjectFake();
        $this->assertTrue($schema->isValidFieldName($fake, 'MyField'));
        $this->assertTrue($schema->isValidFieldName($fake, 'CustomGetter'));
        $this->assertFalse($schema->isValidFieldName($fake, 'fail'));
    }

    public function testLoadsFromConfig()
    {
        $config = [
            'typeNames' =>  [
                DataObjectFake::class => 'testType',
            ]
        ];
        Manager::createFromConfig($config);
        $this->assertEquals('testType', StaticSchema::inst()->typeNameForDataObject(DataObjectFake::class));
        StaticSchema::inst()->setTypeNames([
            DataObjectFake::class => 'otherTestType'
        ]);
        $this->assertEquals('otherTestType', StaticSchema::inst()->typeNameForDataObject(DataObjectFake::class));
    }

    public function testInheritanceTypeNames()
    {
        Config::modify()->set(StaticSchema::class, 'inheritanceTypeSuffix', 'MySuffix');
        $schema = new StaticSchema();
        $type = $schema->typeNameForDataObject(FakeSiteTree::class);
        $union = $schema->inheritanceTypeNameForDataObject(FakeSiteTree::class);
        $this->assertEquals($type . 'MySuffix', $union);
        $union = $schema->inheritanceTypeNameForType($type);
        $this->assertEquals($type . 'MySuffix', $union);
    }

    public function testAncestry()
    {
        $ancestry = StaticSchema::inst()->getAncestry(FakeRedirectorPage::class);
        $this->assertCount(2, $ancestry);
        $this->assertContains(FakePage::class, $ancestry);
        $this->assertContains(FakeSiteTree::class, $ancestry);
        $ancestry = StaticSchema::inst()->getAncestry(FakeSiteTree::class);
        $this->assertCount(0, $ancestry);
    }

    public function testDescendants()
    {
        $descendants = StaticSchema::inst()->getDescendants(FakeSiteTree::class);
        $this->assertCount(2, $descendants);
        $this->assertContains(FakePage::class, $descendants);
        $this->assertContains(FakeRedirectorPage::class, $descendants);
        $descendants = StaticSchema::inst()->getDescendants(FakeRedirectorPage::class);
        $this->assertCount(0, $descendants);
    }

    public function testFetchFromManager()
    {
        $manager = new Manager();
        $typeName = StaticSchema::inst()->typeNameForDataObject(FakeSiteTree::class);
        $inheritedTypeName = StaticSchema::inst()->inheritanceTypeNameForDataObject(FakeSiteTree::class);
        $singleType = new ObjectType(['name' => $typeName]);
        $unionType = new ObjectType(['name' => $inheritedTypeName]);
        $manager->addType($singleType);
        $manager->addType($unionType);

        $result = StaticSchema::inst()
            ->fetchFromManager(FakeSiteTree::class, $manager, StaticSchema::PREFER_UNION);
        $this->assertSame($result, $unionType);

        $result = StaticSchema::inst()
            ->fetchFromManager(FakeSiteTree::class, $manager, StaticSchema::PREFER_SINGLE);
        $this->assertSame($result, $singleType);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/illegal mode/');
        StaticSchema::inst()
            ->fetchFromManager(FakeSiteTree::class, $manager, 'fail');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/could not be resolved/');
        StaticSchema::inst()
            ->fetchFromManager('fail', $manager);
    }
}
