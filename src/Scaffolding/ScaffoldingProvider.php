<?php

namespace SilverStripe\GraphQL\Scaffolding;

use SilverStripe\GraphQL\Scaffolding\Scaffolders\GraphQLScaffolder;

/**
 * Use on classes that update the GraphQL scaffolder
 * @package SilverStripe\GraphQL\Scaffolding
 */
interface ScaffoldingProvider
{
    /**
     * @return mixed
     */
    public function provideGraphQLScaffolding(GraphQLScaffolder $scaffolder);
}