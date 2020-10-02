<?php


namespace SilverStripe\GraphQL\Extensions;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Dev\Build;
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
        if (!self::$done || !static::config()->get('enabled')) {
            Build::singleton()->buildSchema();
            self::$done = true;
        }
    }
}
