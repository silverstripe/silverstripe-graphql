<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;

class Controller extends BaseController
{

    public function index(HTTPRequest $request) {
        $manager = $this->getManager();

        $schema = $manager->getSchema();


    }

    /**
     * @return Manager
     */
    protected function getManager() {
        // Get a service rather than an instance (to allow procedural configuration)
        $config = Config::inst()->get('SilverStripe\GraphQL', 'schema');
        $manager = Injector::inst()->create('SilverStripe\GraphQL\Manager', $config);

        return $manager;
    }

}
