<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface QueryProvider
{
    public function provideQueries(): array;
}
