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
     * @return SilverStripe\GraphQL\Manager
     */
    protected function getManager() {
        // Get a service rather than an instance (to allow procedural configuration)
        $manager = Injector::inst()->get('SilverStripe\GraphQL\Manager');

        // Add types from configuration
        $configTypes = Config::inst()->get('SilverStripe\GraphQL', 'types');
        if($configTypes) {
            foreach($configTypes as $name => $type) {
                $manager->addType($type, $name);
            }
        }

        return $manager;
    }

}
