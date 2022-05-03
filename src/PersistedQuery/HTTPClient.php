<?php

namespace SilverStripe\GraphQL\PersistedQuery;

interface HTTPClient
{
    public function getURL(string $url, int $timeout): ?string;
}
