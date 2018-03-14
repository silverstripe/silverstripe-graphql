<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Schema;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;

class SchemaTest extends SapphireTest
{
    public function testTypeNameForDataObject()
    {
        $typeNames = [
            DataObjectFake::class => 'testType',
        ];
        $schema = new Schema($typeNames);
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
        $schema = new Schema();
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
        $schema = new Schema();
        $schema->setTypeNames($typeNames);
    }

    public function testEnsureAssoc()
    {
        $this->expectException(InvalidArgumentException::class);
        $typeNames = [
            'test1',
            'test2',
        ];
        $schema = new Schema();
        $schema->setTypeNames($typeNames);
    }

    public function testTypeName()
    {
        $schema = new Schema();
        $this->assertEquals('NicelyFormatted_Type', $schema->typeName('Nicely Formatted/Type'));
    }

    public function testIsValidFieldName()
    {
        $schema = new Schema();
        $fake = new DataObjectFake();
        $this->assertTrue($schema->isValidFieldName($fake, 'MyField'));
        $this->assertTrue($schema->isValidFieldName($fake, 'CustomGetter'));
        $this->assertFalse($schema->isValidFieldName($fake, 'fail'));
    }

    public function testLoadsFromConfig()
    {
        Config::modify()->merge(
            Schema::class,
            'typeNames',
            [
                DataObjectFake::class => 'testType'
            ]
        );

        $schema = new Schema();
        $this->assertEquals('testType', $schema->typeNameForDataObject(DataObjectFake::class));
        $schema = new Schema([
            DataObjectFake::class => 'otherTestType'
        ]);
        $this->assertEquals('otherTestType', $schema->typeNameForDataObject(DataObjectFake::class));
    }
}
