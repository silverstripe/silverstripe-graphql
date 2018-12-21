<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Utils\Utils;
use Closure;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use SilverStripe\GraphQL\Serialisation\CodeGen\ArrayDefinition;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\GraphQL\Serialisation\CodeGen\ConfigurableObjectInstantiator;
use SilverStripe\GraphQL\Serialisation\CodeGen\Expression;
use SilverStripe\GraphQL\Serialisation\CodeGen\FunctionDefinition;

class SerialisableUnionType extends UnionType implements CodeGenerator
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

    /**
     * @return ConfigurableObjectInstantiator
     * @throws
     */
    public function toCode()
    {

        /* @var TypeSerialiserInterface $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);

        $types = array_map(function ($type) use ($serialiser) {
            return new Expression($serialiser->exportType($type));
        }, $this->getTypes());

        $config = [
            'types' => new FunctionDefinition(new ArrayDefinition($types)),
            'name' => $this->name,
            'description' => $this->description,
        ];

        if (isset($this->config['resolveTypeFactory'])) {
            $config['resolveTypeFactory'] = $this->config['resolveTypeFactory'];
        } else if (isset($this->config['resolveType'])) {
            $config['resolveType'] = $this->config['resolveType'];
        }

        return new ConfigurableObjectInstantiator(
            ObjectType::class,
            $config
        );
    }
}