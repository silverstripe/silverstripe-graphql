<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;

class FakePersistedQuery implements TestOnly
{
    public function getPersistedQueryMappingString()
    {
        $filePath = $this->getPersistedQueryMappingPath();
        return file_get_contents($filePath);
    }

    public function getPersistedQueryMappingPath()
    {
        return implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'Fixture', 'persisted_query_mapping.json']);
    }
}
