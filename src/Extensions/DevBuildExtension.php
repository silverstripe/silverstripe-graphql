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
     * @var bool
     * @config
     */
    private static $enabled = true;

    /**
     * @var bool
     */
    private static $done = false;

    /**
     * @return void
     */
    public function onAfterBuild()
    {
        if (!static::config()->get('enabled')) {
            return;
        }
        if (!self::$done) {
            // Define custom logger
            $logger = Logger::singleton();
            $logger->setVerbosity(Logger::INFO);
            Injector::inst()->registerService($logger, LoggerInterface::class . '.graphql-build');

            Build::singleton()->buildSchema();
            self::$done = true;
        }
    }
}
