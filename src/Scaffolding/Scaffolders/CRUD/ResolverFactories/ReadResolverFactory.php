<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Storage\Encode\TypeRegistryInterface;
use SilverStripe\ORM\DataList;
use Psr\Container\NotFoundExceptionInterface;
use Exception;
use Closure;

class ReadResolverFactory extends CRUDResolverFactory
{
    /**
     * @param TypeRegistryInterface $registry
     * @return callable|Closure
     * @throws NotFoundExceptionInterface
     */
    public function createResolver(TypeRegistryInterface $registry)
    {
        $class = $this->getDataObjectClass();
        $singleton = $this->getDataObjectInstance();

        return function ($object, array $args, $context, ResolveInfo $info) use ($class, $singleton) {
            if (!$singleton->canView($context['currentUser'])) {
                throw new Exception(sprintf(
                    'Cannot view %s',
                    $class
                ));
            }
            $list = DataList::create($class);
            $this->extend('updateList', $list, $args, $context, $info);

            return $list;
        };
    }
}