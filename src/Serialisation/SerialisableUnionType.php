<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Utils\Utils;
use Serializable;
use Closure;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;

class SerialisableUnionType extends UnionType
{
    use Injectable;

    /**
     * @var Closure
     */
    protected $types;

    /**
     * SerialisableUnionType constructor.
     * @param $config
     * @throws Error
     */
    public function __construct($config)
    {
        parent::__construct($config);
        Utils::invariant(
            !isset($config['resolveTypeFactory']) || $config['resolveTypeFactory'] instanceof ResolverFactory,
            'resolveTypeFactory must be an instance of %s on %s',
            ResolverFactory::class,
            $this->name
        );
    }

    /**
     * Resolves concrete ObjectType for given object value
     *
     * @param $objectValue
     * @param $context
     * @param ResolveInfo $info
     * @return callable|null
     */
    public function resolveType($objectValue, $context, ResolveInfo $info)
    {
        if (isset($this->config['resolveTypeFactory']) && !isset($this->config['resolveType'])) {
            /* @var ResolverFactory $factory */
            $factory = $this->config['resolveTypeFactory'];
            $this->config['resolveType'] = $factory->createResolver();
        }

        return parent::resolveType($objectValue, $context, $info);
    }

    /**
     * @return ObjectType[]
     */
    public function getTypes()
    {
        if (null === $this->types) {
            $types = $this->config['types'];
            if (is_callable($types)) {
                $types = call_user_func($types);
            }
            if (!is_array($types)) {
                throw new InvariantViolation(
                    "Must provide Array of types or a callable which returns " .
                    "such an array for Union {$this->name}"
                );
            }

            $this->types = $types;
        }
        return $this->types;
    }

    public function __sleep()
    {
        $this->types = $this->getTypes();
        unset($this->config['resolveType']);
        unset($this->config['types']);

        return [
            'types',
            'config',
            'name',
            'description',
        ];
    }
}