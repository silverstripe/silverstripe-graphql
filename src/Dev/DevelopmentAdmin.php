<?php


namespace SilverStripe\GraphQL\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\DebugView;
use SilverStripe\Dev\DevelopmentAdmin as RootDevelopmentAdmin;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use Exception;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Schema\Logger;

/**
 * @deprecated 5.3.0 Will be removed without equivalent functionality to replace it
 */
class DevelopmentAdmin extends Controller implements PermissionProvider
{
    private static $allowed_actions = [
        'runRegisteredController'
    ];

    private static $url_handlers = [
        '' => 'index',
        '$Action' => 'runRegisteredController',
    ];

    private static $init_permissions = [
        'ADMIN',
        'ALL_DEV_ADMIN',
        'CAN_DEV_GRAPHQL',
    ];

    public function __construct()
    {
        parent::__construct();
        Deprecation::withNoReplacement(function () {
            Deprecation::notice(
                '5.4.0',
                'Will be removed without equivalent functionality to replace it',
                Deprecation::SCOPE_CLASS
            );
        });
    }

    protected function init()
    {
        parent::init();

        if (RootDevelopmentAdmin::config()->get('deny_non_cli') && !Director::is_cli()) {
            return $this->httpError(404);
        }

        if (!$this->canInit()) {
            Security::permissionFailure($this);
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
            foreach (DevelopmentAdmin::get_links() as $action => $description) {
                echo "<li class=\"$evenOdd\"><a href=\"{$base}dev/graphql/$action\"><b>/dev/graphql/$action:</b>"
                    . " $description</a></li>\n";
                $evenOdd = ($evenOdd == "odd") ? "even" : "odd";
            }

            echo $renderer->renderFooter();

            // CLI mode
        } else {
            echo "SILVERSTRIPE CMS GRAPHQL TOOLS\n--------------------------\n\n";
            echo "You can execute any of the following commands:\n\n";
            foreach (DevelopmentAdmin::get_links() as $action => $description) {
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

    public function canInit(): bool
    {
        return (
            Director::isDev()
            // We need to ensure that DevelopmentAdminTest can simulate permission failures when running
            // "dev/tasks" from CLI.
            || (Director::is_cli() && RootDevelopmentAdmin::config()->get('allow_all_cli'))
            || Permission::check(static::config()->get('init_permissions'))
        );
    }

    public function providePermissions(): array
    {
        return [
            'CAN_DEV_GRAPHQL' => [
                'name' => _t(__CLASS__ . '.CAN_DEV_GRAPHQL_DESCRIPTION', 'Can view and execute /dev/graphql'),
                'help' => _t(__CLASS__ . '.CAN_DEV_GRAPHQL_HELP', 'Can view and execute GraphQL development tools (/dev/graphql).'),
                'category' => RootDevelopmentAdmin::permissionsCategory(),
                'sort' => 80
            ],
        ];
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
