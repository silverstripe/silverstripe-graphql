<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use Closure;
use LogicException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\GraphQL\Serialisation\CodeGen\ArrayDefinition;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\GraphQL\Serialisation\CodeGen\ConfigurableObjectInstantiator;
use SilverStripe\GraphQL\Serialisation\CodeGen\Expression;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class SerialisableFieldDefinition extends FieldDefinition implements TypeStoreConsumer, CodeGenerator
{
    use Injectable;

    /**
     * @var ResolverFactory
     */
    protected $resolverFactory;

    /**
     * @var Type
     */
    protected $type;

    /**
     * @var bool
     */
    protected $pure = false;

    /**
     * @var callable
     */
    protected $typeCreator;

    /**
     * Parent class has its own create() signature, so Injector trait is not compatible.
     * @param array $config
     * @param null $typeName
     * @return FieldDefinition|mixed
     */
    public static function create($config, $typeName = null)
    {
        return Injector::inst()->createWithArgs(static::class, [$config, $typeName]);
    }

    /**
     * SerialisableFieldDefinition constructor.
     * @param array $config
     * @throws Error
     */
    public function __construct(array $config)
    {
        if (isset($config['typeCreator'])) {
            $this->typeCreator = $config['typeCreator'];
            $config['type'] = $this->getType();
        }
        // Workaround for private visibility in parent class
        $this->type = $config['type'];

        parent::__construct($config);

        $factory = isset($config['resolverFactory']) ? $config['resolverFactory'] : null;

        if ($factory) {
            $this->applyResolverFactory($factory);
        }

        // Overwrite the parent implementation :-(
        $this->args = (isset($config['args'])) ? SerialisableFieldArgument::createMap($config['args']) : [];
        $this->pure = isset($config['pure']) ? (bool) $config['pure'] : false;

    }

    /**
     * @param TypeStoreInterface $typeStore
     * @throws NotFoundExceptionInterface
     */
    public function loadFromTypeStore(TypeStoreInterface $typeStore)
    {
        /* @var TypeSerialiser $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);

        // If the type is defined as a string, parse it and load it from the type store
        if (!$this->getType() instanceof Type) {
            $typeCreator = $serialiser->getTypeCreator($this->getType());
            $this->type = $typeCreator($typeStore);
        }

        foreach ($this->args as $arg) {
            $arg->loadFromTypeStore($typeStore);
        }
    }

    /**
     * @return Type
     */
    public function getType()
    {
        if ($this->type instanceof Type) {
            return $this->type;
        }
        if ($this->typeCreator) {
            $this->type = call_user_func($this->typeCreator);
        }
        return $this->type;
    }

    /**
     * @param ResolverFactory $factory
     * @throws Error
     */
    protected function applyResolverFactory($factory)
    {
        if ($this->resolveFn) {
            throw new LogicException(sprintf(
                'Cannot use both a "resolverFactory" and a "resolve" property on field "%s"',
                $this->name
            ));
        }

        Utils::invariant(
            $factory instanceof ResolverFactory,
            'resolverFactory must be an instance of %s on field %s',
            ResolverFactory::class,
            $this->name
        );

        $this->resolverFactory = $factory;

        // Allow for resolvers to be built on the fly. The first time the resolver is called,
        // it uses the factory to lazily build the function. Then it reassigns that function
        // to the resolver property for all future calls.
        $this->resolveFn = function (...$args) {
            $resolver = $this->resolverFactory->createResolver();
            $result = call_user_func($resolver, ...$args);
            $this->resolveFn = $resolver;

            return $result;
        };

    }

    /**
     * @throws Error
     */
    protected function assertSerialisable()
    {

        Utils::invariant(
            $this->resolverFactory instanceof ResolverFactory || !$this->resolveFn instanceof Closure,
            'Resolver for field "%s" cannot be a closure. Use callable array syntax instead.',
            $this->name
        );

        Utils::invariant(
            !$this->resolverFactory instanceof Closure,
            'ResolverFactory on %s is not serialisable',
            $this->name
        );

        Utils::invariant(
            !$this->mapFn instanceof Closure,
            'Map function for field "%s" cannot be a closure. Use callable array syntax instead.',
            $this->name
        );

        Utils::invariant(
            !$this->astNode,
            'Cannot serialise field "%s" that has ASTNode property assigned',
            $this->name
        );

        Utils::invariant(
            !$this->typeCreator || !$this->typeCreator instanceof Closure,
            'typeCreator must use the callable array syntax. Closures are not allowed'
        );

    }

    /**
     * @return array
     * @throws Error
     * @throws NotFoundExceptionInterface
     */
    public function __sleep()
    {
        $this->assertSerialisable();
        /* @var TypeSerialiser $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);

        // If the type is "pure" we can assume there will only be one instance of it,
        // and we do not have to guarantee a singleton.
        if ($this->pure) {
            $this->type = $this->getType();
        } else {
            $this->type = $serialiser->serialiseType($this->getType());
        }
        if ($this->resolverFactory) {
            $this->resolveFn = null;
        }

        return [
            'name',
            'type',
            'args',
            'description',
            'deprecationReason',
            'resolverFactory',
            'resolveFn',
        ];
    }

    /**
     * @throws Error
     */
    public function __wakeup()
    {
        if($this->resolverFactory) {
            $this->resolveFn = null;
            $this->applyResolverFactory($this->resolverFactory);
        }
    }

    /**
     * @param FieldArgument $argument
     * @return ArrayDefinition
     * @throws Error
     * @throws NotFoundExceptionInterface
     */
    protected function createArgCode(FieldArgument $argument)
    {
        Utils::invariant(
            !$argument->astNode,
            'Field argument %s is not serialisable because it has an astNode property assigned',
            $argument->name
        );

        /* @var TypeSerialiser $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);
        $typeCode = $serialiser->exportType($argument->getType());

        return new ArrayDefinition([
            'name' => $argument->name,
            'type' => new Expression($typeCode),
            'description' => $argument->description,
            'defaultValue' => $argument->defaultValue,
        ]);
    }

    /**
     * @param null $varName
     * @return ConfigurableObjectInstantiator|string
     * @throws Error
     * @throws NotFoundExceptionInterface
     */
    public function toCode()
    {
        $this->assertSerialisable();
        /* @var TypeSerialiser $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);

        $typeCode = $serialiser->exportType($this->getType());
        $args = [];
        foreach ($this->args as $argName => $argDef) {
            $args[$argName] = new Expression((string) $this->createArgCode($argDef));
        }
        $config = [
            'name' => $this->name,
            'type' => new Expression($typeCode),

        ];
        if (!empty($this->args)) {
            $config['args'] = new ArrayDefinition($args, 3);
        }
        if ($this->description) {
            $config['description'] = $this->description;
        }
        if ($this->deprecationReason) {
            $config['deprecationReason'] = $this->deprecationReason;
        }

        if ($this->resolverFactory) {
            $config['resolverFactory'] = $this->resolverFactory instanceof CodeGenerator
                ? new Expression((string) $this->resolverFactory->toCode())
                : $this->resolverFactory;
        } else {
            $config['resolve'] = $this->resolveFn;
        }

        return new ConfigurableObjectInstantiator(__CLASS__, $config);
    }

//    public function toCode()
//    {
//        $this->assertSerialisable();
//        /* @var TypeSerialiser $serialiser */
//        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);
//
//        $typeCode = $serialiser->exportType($this->getType());
//        $args = ArrayList::create();
//        foreach ($this->args as $argName => $argDef) {
//            $args->push(ArrayData::create([
//                'Name' => $argName,
//                'Expression' => $this->createArgCode($argDef),
//            ]));
//        }
//        return ArrayData::create([
//            'ClassName' => SerialisableFieldDefinition::class,
//            'Name' => $this->name,
//            'Type' => $typeCode,
//            'Args' => $args,
//            'Description' => $this->description,
//            'DeprecationReason' => $this->deprecationReason,
//            'ResolverFactory' => $this->exportResolverFactory(),
//            'Resolver' => $this->resolveFn ? var_export($this->resolveFn, true) : null,
//        ]);
//
//    }

    protected function exportResolverFactory()
    {
        if (!$this->resolverFactory) {
            return null;
        }
        if ($this->resolverFactory instanceof CodeGenerator) {
            return $this->resolverFactory->toCode();
        }
        return var_export($this->resolverFactory);
    }

}