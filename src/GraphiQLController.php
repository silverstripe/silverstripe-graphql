<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;

class GraphiQLController extends BaseController
{
    /**
     * @var string
     */
    protected $template = 'GraphiQL';

    /**
     * Initialise the controller, sanity check, load javascript
     */
    public function init()
    {
        parent::init();

        if (!Director::isDev()) {
            $this->httpError(403, 'The GraphiQL tool is only available in dev mode');
            return;
        }

        $routes = Director::config()->get('rules');
        $route = null;

        foreach ($routes as $pattern => $controllerInfo) {
            $routeClass = (is_string($controllerInfo)) ? $controllerInfo : $controllerInfo['Controller'];
            if ($routeClass == Controller::class || is_subclass_of($routeClass, Controller::class)) {
                $route = $pattern;
                break;
            }
        }

        if (!$route) {
            throw new \RuntimeException("There are no routes set up for a GraphQL server. You will need to add one to the SilverStripe\Control\Director.rules config setting.");
        }

        Requirements::customScript(
            <<<JS
var GRAPHQL_ROUTE = '{$route}';
JS
        );

        Requirements::javascript(GRAPHQL_DIR.'/client/dist/graphiql.js');
    }
}
