<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Util;

use SilverStripe\Dev\SapphireTest;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Util\OperationList;

class OperationListTest extends SapphireTest
{
    public function testOperationList()
    {
        $list = new OperationList();

        $list->push(new MutationScaffolder('myMutation1', 'test1'));
        $list->push(new MutationScaffolder('myMutation2', 'test2'));

        $this->assertInstanceOf(
            MutationScaffolder::class,
            $list->findByName('myMutation1')
        );
        $this->assertFalse($list->findByName('myMutation3'));

        $list->removeByName('myMutation2');
        $this->assertEquals(1, $list->count());

        $list->removeByName('nothing');
        $this->assertEquals(1, $list->count());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/only accepts instances of/');
        $list->push(new OperationList());
    }
}
