<?php

namespace SilverStripe\GraphQL\Tests\Permission;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Permission\PermissionCheckerAware;
use SilverStripe\GraphQL\Permission\QueryPermissionChecker;
use SilverStripe\Security\Member;
use SilverStripe\ORM\Filterable;
use SilverStripe\Core\Injector\Injector;

class PermissionCheckerAwareTest extends SapphireTest
{
    public function testCanViewPermissionCheckerAddedIfNull()
    {
        $scaffolder = new class() {
            use PermissionCheckerAware;
        };
        $checker = $scaffolder->getPermissionChecker();
        $defaultChecker = Injector::inst()->get(QueryPermissionChecker::class . '.default');
        $this->assertNotNull($checker);
        $this->assertSame(get_class($defaultChecker), get_class($checker));
    }

    public function testDefaultCanViewPermissionCheckerNotAddedIfSet()
    {
        $scaffolder = new class() {
            use PermissionCheckerAware;
        };
        $nonChecker = new class () implements QueryPermissionChecker {
            public function applyToList(Filterable $list, Member $member = null)
            {
                return $list;
            }

            public function checkItem($item, Member $member = null)
            {
                return true;
            }

            public function testMe()
            {
                return true;
            }
        };
        $scaffolder->setPermissionChecker($nonChecker);
        $checker = $scaffolder->getPermissionChecker();
        $this->assertNotNull($checker);
        $this->assertTrue(method_exists($checker, 'testMe'));
    }
}
