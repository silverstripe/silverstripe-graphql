<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;

/**
 * Use on classes that update the GraphQL scaffolder
 */
interface ScaffoldingProvider
{
    /**
     * @return mixed
     */
    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder);
}
