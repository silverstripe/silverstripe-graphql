<?php

namespace SilverStripe\GraphQL\Tasks;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\GraphQL\Dev\Benchmark;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;

class BuildSchemaTask extends BuildTask
{
    private static $segment = 'build-schema';

    /**
     * @param HTTPRequest $request
     * @throws SchemaBuilderException
     */
    public function run($request)
    {
        $clear = $request->getVar('clear') ?: false;
        $keys = $request->getVar('schema')
            ? [$request->getVar('schema')]
            : array_keys(Schema::config()->get('schemas'));
        $keys = array_filter($keys, function ($key) {
            return $key !== Schema::ALL;
        });
        foreach ($keys as $key) {
            Benchmark::start('build-schema-' . $key);
            $schema = Schema::get($key);
            $schema->getReporter()->info(sprintf('--- Building schema "%s" ---', $key));
            if ($clear) {
                $schema->getStore()->clear();
            }
            $schema->save();
            $schema->getReporter()->info(
                Benchmark::end('build-schema-' . $key, 'Built schema in %sms.')
            );

        }
    }
}
