<?php

namespace SilverStripe\GraphQL\PersistedQuery;

interface HTTPClient
{
    /**
     * @param string $url
     * @param int $timeout
     * @return string
     */
    public function getURL($url, $timeout);
}
