<?php

namespace SilverStripe\GraphQL\Resolvers;

use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Storage\Encode\TypeRegistryInterface;
use SilverStripe\ORM\DataObject;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\GraphQL\Storage\Encode\ResolverFactory;
use Exception;
use Closure;

class UnionResolverFactory extends ResolverFactory
{

    /**
     * @param TypeRegistryInterface $registry
     * @return callable|Closure
     * @throws NotFoundExceptionInterface
     */
    public function createResolver(TypeRegistryInterface $registry)
    {
        return function ($obj) use ($registry) {
            if (!$obj instanceof DataObject) {
                throw new Exception(sprintf(
                    'Type with class %s is not a DataObject',
                    get_class($obj)
                ));
            }
            $class = get_class($obj);
            while ($class !== DataObject::class) {
                $typeName = StaticSchema::inst()->typeNameForDataObject($class);
                if ($registry->has($typeName)) {
                    return $registry->has($typeName);
                }
                $class = get_parent_class($class);
            }
            throw new Exception(sprintf(
                'There is no type defined for %s, and none of its ancestors are defined.',
                get_class($obj)
            ));
        };
    }
}