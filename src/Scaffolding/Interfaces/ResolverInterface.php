<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

use SilverStripe\GraphQL\OperationResolver;

/**
 * Applied to classes that resolve queries or mutations
 *
 * @deprecated 2.0..3.0 Use OperationResolver instead
 */
interface ResolverInterface extends OperationResolver
{
}
