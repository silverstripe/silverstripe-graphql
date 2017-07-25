<?php

namespace SilverStripe\GraphQL\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\Manager;

/**
 * Instantiates a GraphQL Manager class as a singleton with its own procedural configuration context
 * so that it can be used further in the request
 */
class RegisterManagerMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $config = Config::inst()->get(Controller::class, 'schema');
        $manager = Manager::createFromConfig($config);
        Injector::inst()->registerService($manager);

        return $delegate($request);
    }
}
