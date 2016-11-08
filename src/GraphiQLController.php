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

		if(!Director::isDev()) {
			return $this->httpError(403, 'The GraphiQL tool is only available in dev mode');
		}

		$routes = Director::config()->get('rules');
		$route = null;
		
		foreach($routes as $pattern => $controllerInfo) {
			if($controllerInfo == Controller::class || is_subclass_of($controllerInfo, Controller::class)) {
				$route = $pattern;
				break;
			}
		}

		if(!$route) {
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