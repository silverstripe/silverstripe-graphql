<?php


namespace SilverStripe\GraphQL\Schema;


interface MutationProvider
{
    public function provideMutations(): array;
}
