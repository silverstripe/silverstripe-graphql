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
        $keys = $request->getVar('schema')
            ? [$request->getVar('schema')]
            : array_keys(Schema::config()->get('schemas'));
        foreach ($keys as $key) {
            Benchmark::start('build-schema-' . $key);
            $schema = Schema::create($key);
            $schema->loadFromConfig();
            $schema->persistSchema();
            Benchmark::end('build-schema-' . $key, 'Built schema in %s ms.');

        }
    }
}
