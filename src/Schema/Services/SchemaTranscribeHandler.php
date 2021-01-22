<?php


namespace SilverStripe\GraphQL\Schema\Services;

use SilverStripe\EventDispatcher\Event\EventContextInterface;
use SilverStripe\EventDispatcher\Event\EventHandlerInterface;
use SilverStripe\GraphQL\Schema\SchemaFactory;
use Exception;

class SchemaTranscribeHandler implements EventHandlerInterface
{
    /**
     * @param EventContextInterface $context
     * @throws Exception
     */
    public function fire(EventContextInterface $context): void
    {
        $schemaKey = $context->getAction();
        $schema = SchemaFactory::singleton()->get($schemaKey);
        if (!$schema) {
            return;
        }

        $inst = SchemaTranscriber::create($schema);
        $inst->writeSchemaToFilesystem();
    }
}
