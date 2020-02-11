<?php

namespace SilverStripe\GraphQL\Tests\Permission;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Permission\CanViewPermissionChecker;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\ORM\ArrayList;

class CanViewPermissionCheckerTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    public function testPermissionCheck()
    {
        $o1 = new DataObjectFake();
        $o1ID = $o1->write();
        $o2 = new DataObjectFake();
        $o2ID = $o2->write();

        $o3 = new RestrictedDataObjectFake();
        $o3->write();
        $o4 = new RestrictedDataObjectFake();
        $o4->write();
        $o5 = new RestrictedDataObjectFake();
        $o5->write();
        $o6 = new RestrictedDataObjectFake();
        $o6->write();

        $checker = new CanViewPermissionChecker();
        $result = $checker->applyToList(ArrayList::create([$o1, $o2]), null);
        $this->assertEquals([$o1ID, $o2ID], $result->column('ID'));

        $result = $checker->applyToList(ArrayList::create([$o1, $o2, $o3, $o4]), null);
        $this->assertEquals([$o1ID, $o2ID], $result->column('ID'));

        $result = $checker->applyToList(ArrayList::create([$o3, $o4]), null);
        $this->assertEmpty($result);

        $this->assertTrue($checker->checkItem($o1));
        $this->assertFalse($checker->checkItem($o4));

        $this->assertEquals(6, DataObjectFake::get()->count());

        $result = $checker->applyToList(DataObjectFake::get()->limit(2, 0), null);
        $this->assertEquals([$o1ID, $o2ID], $result->column('ID'));

        $result = $checker->applyToList(DataObjectFake::get()->limit(2, 1), null);
        $this->assertEquals([$o2ID], $result->column('ID'));

        $result = $checker->applyToList(DataObjectFake::get()->limit(2, 2), null);
        $this->assertEquals(0, $result->count());

        $result = $checker->applyToList(DataObjectFake::get()->limit(2, 4), null);
        $this->assertEquals(0, $result->count());
    }
}
