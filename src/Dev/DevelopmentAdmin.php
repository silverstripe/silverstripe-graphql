<?php


namespace SilverStripe\GraphQL\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\DebugView;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Exception;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Logger;

class DevelopmentAdmin extends Controller
{
    private static $allowed_actions = [
        'runRegisteredController'
    ];

    private static $url_handlers = [
        '' => 'index',
        '$Action' => 'runRegisteredController',
    ];

    protected function init()
    {
        parent::init();

        if (DevelopmentAdmin::config()->get('deny_non_cli') && !Director::is_cli()) {
            return $this->httpError(404);
        }
        // We allow access to this controller regardless of live-status or ADMIN permission only
        // if on CLI.  Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
        $allowAllCLI = DevelopmentAdmin::config()->get('allow_all_cli');
        $canAccess = (
            Director::isDev()
            || (Director::is_cli() && $allowAllCLI)
            // Its important that we don't run this check if dev/build was requested
            || Permission::check("ADMIN")
        );
        if (!$canAccess) {
            Security::permissionFailure($this);
            return;
        }

        // Define custom logger
        $logger = Logger::singleton();
        Injector::inst()->registerService($logger, LoggerInterface::class . '.graphql-build');
    }

    public function index(HTTPRequest $request)
    {
        // Web mode
        if (!Director::is_cli()) {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("Silverstripe CMS GraphQL Tools", Director::absoluteBaseURL());
            $base = Director::baseURL();

            echo '<div class="options"><ul>';
            $evenOdd = "odd";
            foreach (self::get_links() as $action => $description) {
                echo "<li class=\"$evenOdd\"><a href=\"{$base}dev/graphql/$action\"><b>/dev/graphql/$action:</b>"
                    . " $description</a></li>\n";
                $evenOdd = ($evenOdd == "odd") ? "even" : "odd";
            }

            echo $renderer->renderFooter();

            // CLI mode
        } else {
            echo "SILVERSTRIPE CMS GRAPHQL TOOLS\n--------------------------\n\n";
            echo "You can execute any of the following commands:\n\n";
            foreach (self::get_links() as $action => $description) {
                echo "  sake dev/graphql/$action: $description\n";
            }
            echo "\n\n";
        }
    }

    public function runRegisteredController(HTTPRequest $request)
    {
        $controllerClass = null;

        $baseUrlPart = $request->param('Action');
        $reg = Config::inst()->get(static::class, 'registered_controllers');
        if (isset($reg[$baseUrlPart])) {
            $controllerClass = $reg[$baseUrlPart]['controller'];
        }

        if ($controllerClass && class_exists($controllerClass ?? '')) {
            return $controllerClass::create();
        }

        $msg = 'Error: no controller registered in ' . __CLASS__ . ' for: ' . $request->param('Action');
        if (Director::is_cli()) {
            throw new Exception($msg);
        } else {
            $this->httpError(404, $msg);
        }
    }

    /**
     * @return array of url => description
     */
    protected static function get_links(): array
    {
        $links = [];

        $reg = Config::inst()->get(static::class, 'registered_controllers');
        foreach ($reg as $registeredController) {
            if (isset($registeredController['links'])) {
                foreach ($registeredController['links'] as $url => $desc) {
                    $links[$url] = $desc;
                }
            }
        }
        return $links;
    }
}
