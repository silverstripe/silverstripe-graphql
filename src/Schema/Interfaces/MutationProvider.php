<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface MutationProvider
{
    public function provideMutations(): array;
}
