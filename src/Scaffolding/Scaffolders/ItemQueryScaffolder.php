<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Permission\PermissionCheckerAware;
use Exception;

/**
 * Scaffolds a GraphQL query field.
 */
class ItemQueryScaffolder extends QueryScaffolder
{
    use PermissionCheckerAware;

    /**
     * @return callable|\Closure
     */
    protected function createResolverFunction()
    {
        $resolverFn = parent::createResolverFunction();

        // Wrap resolver in permission checks.
        $checker = $this->getPermissionChecker();
        if (!$checker) {
            return $resolverFn;
        }

        return function ($obj, array $args, $context, ResolveInfo $info) use ($resolverFn, $checker) {
            $item = call_user_func_array($resolverFn, func_get_args());
            $currentUser = $context['currentUser'];
            if (!$checker->checkItem($item, $currentUser)) {
                throw new Exception(sprintf(
                    'Cannot view %s',
                    $this->getDataObjectClass()
                ));
            }

            return $item;
        };
    }

    /**
     * @param Manager $manager
     *
     * @return array
     */
    public function scaffold(Manager $manager)
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'args' => $this->createArgs($manager),
            'type' => $this->getType($manager),
            'resolve' => $this->createResolverFunction(),
        ];
    }
}
