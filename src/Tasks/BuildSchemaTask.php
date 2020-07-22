<?php

namespace SilverStripe\GraphQL\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\GraphQL\Schema\SchemaBuilder;

class BuildSchemaTask extends BuildTask
{
    private static $segment = 'build-schema';

    public function run($request)
    {
        $startTime = microtime(true);
        $builder = SchemaBuilder::create('default');
        $builder->loadFromConfig();
        $builder->persistSchema();
        $endTime = microtime(true);

        $elapsedTime = round($endTime - $startTime, 2);

        echo "Built schema in $elapsedTime seconds\n";
    }
}
