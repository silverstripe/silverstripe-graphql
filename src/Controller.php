<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\ORM\Versioning\Versioned;
use Exception;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Top level controller for handling graphql requests.
 * @todo CSRF protection (or token-based auth)
 */
class Controller extends BaseController
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Handles requests to /graphql (index action)
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function index(HTTPRequest $request)
    {
        $stage = $request->param('Stage');
        if ($stage && in_array($stage, [Versioned::DRAFT, Versioned::LIVE])) {
            Versioned::set_stage($stage);
        }
        $contentType = $request->getHeader('Content-Type') ?: $request->getHeader('content-type');
        $isJson = preg_match('#^application/json\b#', $contentType);
        if ($isJson) {
            $rawBody = $request->getBody();
            $data = json_decode($rawBody ?: '', true);
            $query = isset($data['query']) ? $data['query'] : null;
            $variables = isset($data['variables']) ? json_decode($data['variables'], true) : null;
        } else {
            $query = $request->requestVar('query');
            $variables = json_decode($request->requestVar('variables'), true);
        }

        $this->setManager($manager = $this->getManager());

        try {
            // Check authentication
            $member = $this->getAuthHandler()->requireAuthentication($request);
            if ($member) {
                $manager->setMember($member);
            }

            // Check authorisation
            $permissions = $request->param('Permissions');
            if ($permissions) {
                if (!$member) {
                    throw new \Exception("Authentication required");
                }
                $allowed = Permission::checkMember($member, $permissions);
                if (!$allowed) {
                    throw new \Exception("Not authorised");
                }
            }

            // Run query
            $result = $manager->query($query, $variables);
        } catch (Exception $exception) {
            $error = ['message' => $exception->getMessage()];

            if (Director::isDev()) {
                $error['code'] = $exception->getCode();
                $error['file'] = $exception->getFile();
                $error['line'] = $exception->getLine();
                $error['trace'] = $exception->getTrace();
            }

            $result = [
                'errors' => [$error]
            ];
        }

        return (new HTTPResponse(json_encode($result)))
            ->addHeader('Content-Type', 'application/json');
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
        $config = Config::inst()->get('SilverStripe\GraphQL', 'schema');
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

    /**
     * Get an instance of the authorization Handler to manage any authentication requirements
     *
     * @return Handler
     */
    public function getAuthHandler()
    {
        return new Handler;
    }
}
