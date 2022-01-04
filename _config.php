<?php

use GraphQL\Executor\Executor;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Executor\EventDispatcherAwareExecutor;

// Set to 3.0.0 to show APIs marked for removal at that version
Deprecation::notification_version('2.0.0', 'silverstripe/graphql');

Executor::setImplementationFactory([EventDispatcherAwareExecutor::class, 'create']);
