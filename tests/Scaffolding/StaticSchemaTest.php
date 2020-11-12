<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding;

use GraphQL\Type\Definition\ObjectType;
use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeFieldAccessor;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ArrayData;

class StaticSchemaTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        StaticSchema::reset();
    }

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
        Manager::create()->applyConfig($config);
        $this->assertEquals('testType', StaticSchema::inst()->typeNameForDataObject(DataObjectFake::class));
        StaticSchema::inst()->setTypeNames([
            DataObjectFake::class => 'otherTestType'
        ]);
        $this->assertEquals('otherTestType', StaticSchema::inst()->typeNameForDataObject(DataObjectFake::class));
    }

    public function testLoadSchemaName()
    {
        $config = [
            'schema1' => [
                'typeNames' =>  [
                    DataObjectFake::class => 'testType1',
                ],
            ],
            'schema2' => [],
            'schema3' => [
                'typeNames' => [
                    DataObjectFake::class => 'testType3',
                ]
            ]
        ];
        $inst = StaticSchema::inst();
        Config::modify()->set(Manager::class, 'schemas', $config);
        $this->assertEquals(
            'SilverStripeDataObjectFake',
            $inst->typeNameForDataObject(DataObjectFake::class)
        );
        $inst->load('schema1');
        $this->assertEquals(
            'testType1',
            $inst->typeNameForDataObject(DataObjectFake::class)
        );
        $inst->load('schema2');
        $this->assertEquals(
            'SilverStripeDataObjectFake',
            $inst->typeNameForDataObject(DataObjectFake::class)
        );
        $inst->load('schema3');
        $this->assertEquals(
            'testType3',
            $inst->typeNameForDataObject(DataObjectFake::class)
        );
        $inst->load('notASchema');
        $this->assertEquals(
            'SilverStripeDataObjectFake',
            $inst->typeNameForDataObject(DataObjectFake::class)
        );
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

    public function testInstance()
    {
        $inst1 = StaticSchema::inst();
        $inst2 = StaticSchema::inst();

        $this->assertSame($inst1, $inst2);

        $new = new StaticSchema();
        StaticSchema::setInstance($new);
        $this->assertSame($new, StaticSchema::inst());

        StaticSchema::reset();
        $this->assertNotSame($new, StaticSchema::inst());
    }

    public function testFormatField()
    {
        $result = StaticSchema::inst()->formatField('Foo');
        $this->assertEquals('Foo', $result);

        StaticSchema::inst()->setFieldFormatter('strrev');
        $result = StaticSchema::inst()->formatField('abc');
        $this->assertEquals('cba', $result);
    }

    public function testFormatFields()
    {
        $result = StaticSchema::inst()->formatFields(['Foo', 'Bar']);
        $this->assertEquals(['Foo', 'Bar'], $result);

        StaticSchema::inst()->setFieldFormatter('strrev');
        $result = StaticSchema::inst()->formatFields(['abc', '123']);
        $this->assertEquals(['cba', '321'], $result);
    }

    public function testFormatKeys()
    {
        $result = StaticSchema::inst()->formatKeys(['Foo', 'Bar']);
        $this->assertEquals(['Foo', 'Bar'], $result);

        StaticSchema::inst()->setFieldFormatter('strrev');
        $result = StaticSchema::inst()->formatKeys(['Foo', 'Bar']);
        $this->assertEquals(['Foo', 'Bar'], $result);

        $result = StaticSchema::inst()->formatKeys(['Foo' => 'test1', 'Bar' => 'test2']);
        $this->assertArrayHasKey('ooF', $result);
        $this->assertArrayHasKey('raB', $result);
        $this->assertEquals('test1', $result['ooF']);
        $this->assertEquals('test2', $result['raB']);
    }

    public function testExtractKeys()
    {
        $arr = ['Foo' => 'test1', 'Bar' => 'test2'];
        $result = StaticSchema::inst()->extractKeys(['Foo', 'Bar'], $arr);
        $this->assertEquals(['test1', 'test2'], $result);

        $arr = ['ooF' => 'test1', 'raB' => 'test2'];
        StaticSchema::inst()->setFieldFormatter('strrev');
        $result = StaticSchema::inst()->extractKeys(['Foo', 'Bar'], $arr);
        $this->assertEquals(['test1', 'test2'], $result);

        $result = StaticSchema::inst()->extractKeys(['Foo', 'NotExists'], $arr);
        $this->assertEquals(['test1', null], $result);

        $this->expectException(\PHPUnit_Framework_Error_Notice::class);
        StaticSchema::inst()->extractKeys(['Foo', 'NotExists'], $arr, false);
    }

    public function testAccessField()
    {
        StaticSchema::inst()->setFieldAccessor(new FakeFieldAccessor());

        $obj = new ArrayData(['Foo' => 'test1', 'Bar' => 'test2']);
        $result = StaticSchema::inst()->accessField($obj, 'Foo');
        $this->assertInstanceOf(DBField::class, $result);
        $this->assertEquals('test1', $result->getValue());

        $result = StaticSchema::inst()->accessField($obj, 'ooF');
        $this->assertInstanceOf(DBField::class, $result);
        $this->assertEquals('test1', $result->getValue());

        $result = StaticSchema::inst()->isValidFieldName($obj, 'Foo');
        $this->assertTrue($result);

        $result = StaticSchema::inst()->isValidFieldName($obj, 'ooF');
        $this->assertTrue($result);

        $result = StaticSchema::inst()->isValidFieldName($obj, 'ofo');
        $this->assertFalse($result);
    }
}
