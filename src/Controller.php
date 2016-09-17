<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;

class Controller extends BaseController
{

    /**
     * @var Manager
     */
    protected $manager;

    public function index(HTTPRequest $request) {
        $query = $request->getVar('query');
        $params = $request->getVar('params');

        if(is_string($params)) {
            $params = json_decode($params, true);
        }

        $manager = $this->getManager();
        $response = $manager->query($query, $params);

        return (new HTTPResponse(json_encode($response)))
            ->addHeader('Content-Type', 'text/json');
    }

    /**
     * @return Manager
     */
    public function getManager() {
        if($this->manager) {
            return $this->manager;
        }

        // Get a service rather than an instance (to allow procedural configuration)
        $config = Config::inst()->get('SilverStripe\GraphQL', 'schema');
        $manager = Injector::inst()->create(Manager::class, $config);

        return $manager;
    }

    /**
     * @param Manager $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

}
