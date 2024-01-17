<?php

namespace SilverStripe\GraphQL\Extensions;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\GraphQL\Controller;

/**
 * @extends Extension<LeftAndMain>
 */
class ClientConfigProvider extends Extension
{
    public function updateClientConfig(array &$config): void
    {
        if (!isset($config['graphql'])) {
            $config['graphql'] = [];
        }

        $config['graphql']['cachedTypenames'] = Config::inst()->get(Controller::class, 'cache_types_in_filesystem');
    }
}
