<?php


namespace SilverStripe\GraphQL\Extensions;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Dev\Build;
use SilverStripe\GraphQL\Schema\Logger;
use SilverStripe\ORM\DataExtension;

class DevBuildExtension extends DataExtension
{
    use Configurable;

    /**
     * @config
     */
    private static bool $enabled = true;

    private static bool $done = false;

    public function onAfterBuild(): void
    {
        if (!static::config()->get('enabled')) {
            return;
        }
        if (!self::$done) {
            // Get the current graphQL logger
            $defaultLogger = Injector::inst()->get(LoggerInterface::class . '.graphql-build');

            try {
                // Define custom logger
                $logger = Logger::singleton();
                $logger->setVerbosity(Logger::INFO);
                Injector::inst()->registerService($logger, LoggerInterface::class . '.graphql-build');

                Build::singleton()->buildSchema();
                self::$done = true;
            } finally {
                // Restore default logger back to its starting state
                Injector::inst()->registerService($defaultLogger, LoggerInterface::class . '.graphql-build');
            }
        }
    }
}
