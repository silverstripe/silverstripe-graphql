<?php

namespace SilverStripe\GraphQL\Resolvers;

use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Storage\Encode\RegistryAwareClosureFactory;
use SilverStripe\GraphQL\Storage\Encode\TypeRegistryInterface;
use SilverStripe\ORM\DataObject;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\GraphQL\Storage\Encode\ClosureFactory;
use Exception;
use Closure;

class UnionResolverFactory extends RegistryAwareClosureFactory
{

    /**
     * @param TypeRegistryInterface $registry
     * @return callable|Closure
     * @throws NotFoundExceptionInterface
     */
    public function createClosure(TypeRegistryInterface $registry)
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
                if ($registry->hasType($typeName)) {
                    return $registry->hasType($typeName);
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