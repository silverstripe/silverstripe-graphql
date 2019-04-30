<?php

namespace SilverStripe\GraphQL\Dev\State;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\State\TestState;
use SilverStripe\GraphQL\Controller;

class DisableTypeCacheState implements TestState
{

    /**
     * Called on setup
     *
     * @param SapphireTest $test
     */
    public function setUp(SapphireTest $test)
    {
        // no-op
    }

    /**
     * Called on tear down
     *
     * @param SapphireTest $test
     */
    public function tearDown(SapphireTest $test)
    {
        // no-op
    }

    /**
     * Called once on setup
     *
     * @param string $class Class being setup
     */
    public function setUpOnce($class)
    {
        Config::modify()->set(Controller::class, 'cache_on_flush', false);
    }

    /**
     * Called once on tear down
     *
     * @param string $class Class being torn down
     */
    public function tearDownOnce($class)
    {
        // no-op
    }
}
