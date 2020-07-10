<?php


namespace SilverStripe\GraphQL\Schema;


interface QueryProvider
{
    public function provideQueries(): array;
}
