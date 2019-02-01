<?php

namespace SilverStripe\GraphQL\Resolvers;

use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Schema\Encoding\Factories\RegistryAwareClosureFactory;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface;
use SilverStripe\ORM\DataObject;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\GraphQL\Storage\Encode\ClosureFactory;
use Exception;
use Closure;

class UnionResolverFactory extends RegistryAwareClosureFactory
{

    /**
     * @var array Maps class names to type names.
     */
    protected $classMap = [];

    /**
     * @return array
     */
    public function getClassMap()
    {
        return $this->classMap;
    }

    /**
     * @param $classMap
     * @return $this
     */
    public function setClassMap($classMap)
    {
        $this->classMap = $classMap;

        return $this;
    }

    /**
     * @param \SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface $registry
     * @return callable|Closure
     * @throws NotFoundExceptionInterface
     */
    public function createClosure(TypeRegistryInterface $registry)
    {
        return function ($obj) use ($registry) {
            $schema = StaticSchema::inst();

            // Try explicit mapping of class names to types
            $objClass = get_class($obj);
            if (array_key_exists($objClass, $this->classMap)) {
                $typeName = $this->classMap[$objClass];
                if ($registry->hasType($typeName)) {
                    return $registry->hasType($typeName);
                }
            }

            // Fall back to auto-detection for type names on DataObjects
            if (!$obj instanceof DataObject) {
                throw new Exception(sprintf(
                    'Type with class %s is not a DataObject',
                    get_class($obj)
                ));
            }
            $class = get_class($obj);
            while ($class !== DataObject::class) {
                $typeName = $schema->typeNameForDataObject($class);
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
