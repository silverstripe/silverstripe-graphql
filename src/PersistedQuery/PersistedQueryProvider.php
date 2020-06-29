<?php


namespace SilverStripe\GraphQL\PersistedQuery;


interface PersistedQueryProvider
{
    public function getQueryFromPersistedID($id): ?string;
}
