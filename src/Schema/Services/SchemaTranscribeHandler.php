<?php


namespace SilverStripe\GraphQL\Schema\Services;

use SilverStripe\EventDispatcher\Event\EventContextInterface;
use SilverStripe\EventDispatcher\Event\EventHandlerInterface;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use Exception;

class SchemaTranscribeHandler implements EventHandlerInterface
{
    /**
     * @throws Exception
     */
    public function fire(EventContextInterface $context): void
    {
        $schemaKey = $context->getAction();
        $schema = SchemaBuilder::singleton()->getSchema($schemaKey);
        if (!$schema) {
            return;
        }

        $inst = SchemaTranscriber::create($schema, $schemaKey);
        $inst->writeSchemaToFilesystem();
    }
}
