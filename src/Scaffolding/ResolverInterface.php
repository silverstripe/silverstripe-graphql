<?php

namespace SilverStripe\GraphQL\Scaffolding;

/**
 * Applied to classes that resolve queries or mutations
 */
interface ResolverInterface
{
    /**
     * @param DataObjectInterface $object
     * @param array $args
     * @param $context
     * @param $info
     * @return mixed
     */
    public function resolve($object, $args, $context, $info);

}