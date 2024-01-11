<?php

namespace SilverStripe\GraphQL\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\GraphQL\Schema\Logger;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Dev\Benchmark;
use SilverStripe\TestSession\TestSessionEnvironment;

/**
 * @extends Extension<TestSessionEnvironment>
 */
class TestSessionEnvironmentExtension extends Extension
{
    /**
     * Build the graphql schema after a new testsession is started
     * This is to ensure that the schema is available when a behat test is run, particularly on CI
     * This does laregely the same thing as SilverStripe\GraphQL\Dev\Build::buildSchema(), though
     * it also checks for the existance of persisted schemas first do that the schema is not rebuilt
     * after each behat scenario
     */
    public function onAfterStartTestSession(): void
    {
        $logger = Logger::singleton();
        $keys = array_keys(Schema::config()->get('schemas') ?? []);
        $keys = array_filter($keys ?? [], function ($key) {
            return $key !== Schema::ALL;
        });
        $builder = SchemaBuilder::singleton();
        foreach ($keys as $key) {
            // skip if the schema has already been built and persisted in the filesystem
            if ($builder->getSchema($key)) {
                continue;
            }
            Benchmark::start('build-schema-' . $key);
            $schema = $builder->boot($key);
            try {
                $builder->build($schema);
            } catch (EmptySchemaException $e) {
                $logger->warning('Schema ' . $key . ' is empty. Skipping.');
            }
            $logger->info(
                Benchmark::end('build-schema-' . $key, 'Built schema in %sms.')
            );
        }
    }
}
