<?php

namespace SilverStripe\GraphQL\Tests\Permission;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Permission\CanViewPermissionChecker;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\ORM\ArrayList;

class CanViewPermissionCheckerTest extends SapphireTest
{
    protected static $fixture_file = 'CanViewPermissionCheckerTest.yml';

    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    public function testAppyToListNoRestricted()
    {
        $fake1 = $this->objFromFixture(DataObjectFake::class, 'fake1');
        $fake2 = $this->objFromFixture(DataObjectFake::class, 'fake2');

        $checker = new CanViewPermissionChecker();
        $result = $checker->applyToList(ArrayList::create([$fake1, $fake2]), null);
        $this->assertEquals([$fake1->ID, $fake2->ID], $result->column('ID'));
    }

    public function testAppyToListOmitRestricted()
    {
        $fake1 = $this->objFromFixture(DataObjectFake::class, 'fake1');
        $fake2 = $this->objFromFixture(DataObjectFake::class, 'fake2');
        $fake3 = $this->objFromFixture(RestrictedDataObjectFake::class, 'fake3');
        $fake4 = $this->objFromFixture(RestrictedDataObjectFake::class, 'fake4');
        $checker = new CanViewPermissionChecker();

        $result = $checker->applyToList(ArrayList::create([$fake1, $fake2, $fake3, $fake4]), null);
        $this->assertEquals([$fake1->ID, $fake2->ID], $result->column('ID'));
    }

    public function testAppyToListRestrcitedOnly()
    {
        $fake3 = $this->objFromFixture(RestrictedDataObjectFake::class, 'fake3');
        $fake4 = $this->objFromFixture(RestrictedDataObjectFake::class, 'fake4');
        $checker = new CanViewPermissionChecker();

        $result = $checker->applyToList(ArrayList::create([$fake3, $fake4]), null);
        $this->assertEmpty($result);
    }

    public function testApplyToListWithDataListNoRestricted()
    {
        $fake1 = $this->objFromFixture(DataObjectFake::class, 'fake1');
        $fake2 = $this->objFromFixture(DataObjectFake::class, 'fake2');
        $checker = new CanViewPermissionChecker();

        $result = $checker->applyToList(DataObjectFake::get()->filter('ClassName', DataObjectFake::class), null);
        $this->assertEquals([$fake1->ID, $fake2->ID], $result->column('ID'));
    }

    public function testApplyToListWithDataListRestrictedOnly()
    {
        $checker = new CanViewPermissionChecker();
        $result = $checker->applyToList(DataObjectFake::get()->filter('ClassName', RestrictedDataObjectFake::class), null);
        $this->assertEquals(0, $result->count());
    }

    public function testCheckItem()
    {
        $fake1 = $this->objFromFixture(DataObjectFake::class, 'fake1');
        $fake4 = $this->objFromFixture(RestrictedDataObjectFake::class, 'fake4');
        $checker = new CanViewPermissionChecker();

        $this->assertTrue($checker->checkItem($fake1));
        $this->assertFalse($checker->checkItem($fake4));
    }
}
