<?php

namespace SilverStripe\GraphQL\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\GraphQL\Controller as GraphQLController;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Psr\Container\NotFoundExceptionInterface;
use GraphQL\Error\Error;
use Psr\SimpleCache\InvalidArgumentException as CacheException;
use RuntimeException;

class SchemaGeneratorController extends Controller
{
    private static $url_handlers = [
        '$SchemaKey!' => 'build',
    ];

    private static $allowed_actions = [
        'build'
    ];

    /**
     * @var bool
     * @config
     */
    private static $allow_all_cli = true;

    public function index(HTTPRequest $request)
    {
        user_error('Usage: dev/schema/&lt;schemaKey&gt;', E_USER_NOTICE);
    }
    /**
     * @param HTTPRequest $request
     * @throws NotFoundExceptionInterface
     * @throws Error
     * @throws CacheException
     */
    public function build(HTTPRequest $request)
    {
        $allowAllCLI = static::config()->get('allow_all_cli');
        $canAccess = (
            Director::isDev()
            || (Director::is_cli() && $allowAllCLI)
            || Permission::check("ADMIN")
        );
        if (!$canAccess) {
            Security::permissionFailure(
                $this,
                "This page is secured and you need administrator rights to access it. " .
                "Enter your credentials below and we will send you right along."
            );
        }

        $schemaKey = $request->param('SchemaKey');
        $selectedManager = null;
        /* @var Controller $controller */
        foreach (GraphQLController::getRoutedControllers() as $controller) {
            $manager = $controller->getManager($request);
            if ($manager->getSchemaKey() == $schemaKey) {
                $selectedManager = $manager;
                break;
            }
        }

        if (!$selectedManager) {
            throw new RuntimeException(sprintf('Could not find a GraphQL controller using schema "%s"', $schemaKey));
        }

        $start = microtime(true);
        $selectedManager->regenerate();
        $end = microtime(true);
        $diff = $end - $start;

        echo sprintf('Schema "%s" rebuilt in %s seconds', $schemaKey, number_format($diff, 2));
    }

}