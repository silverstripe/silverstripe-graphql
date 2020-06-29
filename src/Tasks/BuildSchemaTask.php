<?php

namespace SilverStripe\GraphQL\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\GraphQL\Schema\SchemaBuilder;

class BuildSchemaTask extends BuildTask
{
    private static $segment = 'build-schema';

    public function run($request)
    {
        $builder = SchemaBuilder::create('default');
        $builder->loadFromConfig();
        $builder->persistSchema();
    }
}
