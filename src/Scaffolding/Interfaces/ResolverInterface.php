<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Applied to classes that resolve queries or mutations
 */
interface ResolverInterface
{
    /**
     * @param DataObjectInterface $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolve($object, $args, $context, $info);
}
