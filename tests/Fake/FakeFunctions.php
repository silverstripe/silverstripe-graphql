<?php


namespace SilverStripe\GraphQL\Tests\Fake;


use SilverStripe\Core\ClassInfo;

class FakeFunctions
{
    public static function fakeFormatter(string $className)
    {
        return strrev(ClassInfo::shortName($className));
    }
}
