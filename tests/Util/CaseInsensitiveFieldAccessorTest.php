<?php

namespace SilverStripe\GraphQL\Tests\Util;

use SilverStripe\GraphQL\Util\CaseInsensitiveFieldAccessor;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\Dev\SapphireTest;

class CaseInsensitiveFieldAccessorTest extends SapphireTest
{
    public function testGetValueWithOriginalCasing()
    {
        $fake = new DataObjectFake([
            'MyField' => 'myValue'
        ]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $this->assertEquals('myValue', $mapper->getValue($fake, 'MyField'));
    }

    public function testGetValueWithDifferentCasing()
    {
        $fake = new DataObjectFake([
            'MyField' => 'myValue'
        ]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $this->assertEquals('myValue', $mapper->getValue($fake, 'myfield'));
    }

    public function testGetValueWithCustomGetter()
    {
        $fake = new DataObjectFake([]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $this->assertEquals('customGetterValue', $mapper->getValue($fake, 'customGetter'));
    }

    public function testGetValueWithMethod()
    {
        $fake = new DataObjectFake([]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $this->assertEquals('customMethodValue', $mapper->getValue($fake, 'customMethod'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetValueWithUnknownFieldThrowsException()
    {
        $fake = new DataObjectFake([]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $mapper->getValue($fake, 'unknownField');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetValueWithCustomOpts()
    {
        $fake = new DataObjectFake([
            'MyField' => 'myValue'
        ]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $opts = [
            // only check for methods
            CaseInsensitiveFieldAccessor::HAS_FIELD => false,
            CaseInsensitiveFieldAccessor::DATAOBJECT => false,
        ];
        $mapper->getValue($fake, 'MyField', $opts);
    }

    public function testSetValueWithOriginalCasing()
    {
        $fake = new DataObjectFake([
            'MyField' => 'myValue'
        ]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $mapper->setValue($fake, 'MyField', 'myNewValue');
        $this->assertEquals('myNewValue', $fake->MyField);
    }

    public function testSetValueWithDifferentCasing()
    {
        $fake = new DataObjectFake([
            'MyField' => 'myValue'
        ]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $mapper->setValue($fake, 'myfield', 'myNewValue');
        $this->assertEquals('myNewValue', $fake->MyField);
    }

    public function testSetValueWithCustomGetter()
    {
        $fake = new DataObjectFake([]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $mapper->setValue($fake, 'customsetterfield', 'myNewValue');
        $this->assertEquals('myNewValue', $fake->customSetterFieldResult);
    }

    public function testSetValueWithMethod()
    {
        $fake = new DataObjectFake([]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $mapper->setValue($fake, 'customsettermethod', 'myNewValue');
        $this->assertEquals('myNewValue', $fake->customSetterMethodResult);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetValueWithUnknownFieldThrowsException()
    {
        $fake = new DataObjectFake([]);
        $mapper = new CaseInsensitiveFieldAccessor();
        $mapper->setValue($fake, 'unknownField', true);
    }
}
