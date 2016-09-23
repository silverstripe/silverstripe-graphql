<?php

namespace Chillu\GraphQL;

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

    public function index(HTTPRequest $request)
    {
        $isJson = (
            $request->getHeader('Content-Type') === 'application/json'
            || $request->getHeader('content-type') === 'application/json'
        );
        if ($isJson) {
            $rawBody = $request->getBody();
            $data = json_decode($rawBody ?: '', true);
        } else {
            $data = $request->requestVars();
        }

        $query = isset($data['query']) ? $data['query'] : null;
        $variables = isset($data['variables']) ? $data['variables'] : null;

        // Some clients (e.g. GraphiQL) double encode as string
        if(is_string($variables)) {
            $variables = json_decode($variables, true);
        }

        $manager = $this->getManager();
        $response = $manager->query($query, $variables);

        return (new HTTPResponse(json_encode($response)))
            ->addHeader('Content-Type', 'text/json');
    }

    /**
     * @return Manager
     */
    public function getManager()
    {
        if ($this->manager) {
            return $this->manager;
        }

        // Get a service rather than an instance (to allow procedural configuration)
        $config = Config::inst()->get('Chillu\GraphQL', 'schema');
        $manager = Manager::createFromConfig($config);

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
