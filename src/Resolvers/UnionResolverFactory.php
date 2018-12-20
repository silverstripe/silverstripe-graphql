<?php

namespace SilverStripe\GraphQL\Resolvers;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\ORM\DataObject;
use Psr\Container\NotFoundExceptionInterface;
use Exception;
use Closure;

class UnionResolverFactory implements ResolverFactory, CodeGenerator
{

    /**
     * @return Closure
     * @throws NotFoundExceptionInterface
     */
    public function createResolver()
    {
        // Todo: remove this coupling.
        $typeStore = Injector::inst()->get(TypeStoreInterface::class);

        return function ($obj) use ($typeStore) {
            if (!$obj instanceof DataObject) {
                throw new Exception(sprintf(
                    'Type with class %s is not a DataObject',
                    get_class($obj)
                ));
            }
            $class = get_class($obj);
            while ($class !== DataObject::class) {
                $typeName = StaticSchema::inst()->typeNameForDataObject($class);
                if ($typeStore->hasType($typeName)) {
                    return $typeStore->getType($typeName);
                }
                $class = get_parent_class($class);
            }
            throw new Exception(sprintf(
                'There is no type defined for %s, and none of its ancestors are defined.',
                get_class($obj)
            ));
        };
    }

    public function toCode()
    {
        return sprintf('new %s()', __CLASS__);
    }
}