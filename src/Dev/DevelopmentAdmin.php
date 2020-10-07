<?php


namespace SilverStripe\GraphQL\Dev;


use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\DevelopmentAdmin as BaseDevelopmentAdmin;

class DevelopmentAdmin extends BaseDevelopmentAdmin
{
    private static $allowed_actions = [
        'runRegisteredController'
    ];

    // Waiting on https://github.com/silverstripe/silverstripe-framework/pull/9702
    public function runRegisteredController(HTTPRequest $request)
    {
        $controllerClass = null;

        $baseUrlPart = $request->param('Action');
        $reg = Config::inst()->get(static::class, 'registered_controllers');
        if (isset($reg[$baseUrlPart])) {
            $controllerClass = $reg[$baseUrlPart]['controller'];
        }

        if ($controllerClass && class_exists($controllerClass)) {
            return $controllerClass::create();
        }

        $msg = 'Error: no controller registered in ' . __CLASS__ . ' for: ' . $request->param('Action');
        if (Director::is_cli()) {
            // in CLI we cant use httpError because of a bug with stuff being in the output already, see DevAdminControllerTest
            throw new Exception($msg);
        } else {
            $this->httpError(404, $msg);
        }
    }

}
