<?php

namespace SilverStripe\GraphQL\Tests\Fake;

class RestrictedDataObjectFake extends DataObjectFake
{
    private static $table_name = "GraphQL_RestrictedDataObjectFake";

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canView($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }
}
