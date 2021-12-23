<?php


namespace SilverStripe\GraphQL\Dev\State;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\State\TestState;
use SilverStripe\GraphQL\Schema\Storage\NaiveNameObfuscator;
use SilverStripe\GraphQL\Schema\Storage\NameObfuscator;

class DebugSchemaState implements TestState
{
    public function setUp(SapphireTest $test)
    {
        // no-op
    }

    public function tearDown(SapphireTest $test)
    {
        // no-op
    }

    public function setUpOnce($class)
    {
        Environment::setEnv('DEBUG_SCHEMA', 1);
    }

    public function tearDownOnce($class)
    {
        // no-op
    }
}
