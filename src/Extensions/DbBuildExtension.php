<?php


namespace SilverStripe\GraphQL\Extensions;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Command\DbBuild;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\GraphQL\Dev\SchemaBuild;
use SilverStripe\GraphQL\Schema\Logger;

/**
 * @extends Extension<DbBuild>
 */
class DbBuildExtension extends Extension
{
    use Configurable;

    /**
     * @config
     */
    private static bool $enabled = true;

    protected function onAfterBuild(PolyOutput $output): void
    {
        if (!static::config()->get('enabled')) {
            return;
        }

        // Get the current graphQL logger
        $defaultLogger = Injector::inst()->get(LoggerInterface::class . '.graphql-build');

        try {
            // Define custom logger
            $logger = Logger::singleton();
            $logger->setOutput($output);
            Injector::inst()->registerService($logger, LoggerInterface::class . '.graphql-build');

            SchemaBuild::singleton()->buildSchema();
        } finally {
            // Restore default logger back to its starting state
            Injector::inst()->registerService($defaultLogger, LoggerInterface::class . '.graphql-build');
        }
    }
}
