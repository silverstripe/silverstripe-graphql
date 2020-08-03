<?php

namespace SilverStripe\GraphQL\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\GraphQL\Dev\Benchmark;
use SilverStripe\GraphQL\Schema\Schema;

class BuildSchemaTask extends BuildTask
{
    private static $segment = 'build-schema';

    public function run($request)
    {
        Benchmark::start('build-schema');
        $schema = Schema::create('default');
        $schema->loadFromConfig();
        $schema->persistSchema();
        Benchmark::end('build-schema', 'Built schema in %s ms.');
    }
}
