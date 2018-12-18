<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Utils\Utils;
use Closure;
use LogicException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use Psr\Container\NotFoundExceptionInterface;

class SerialisableFieldDefinition extends FieldDefinition implements TypeStoreConsumer
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

        return [
            'name',
            'type',
            'args',
            'description',
            'deprecationReason',
            'resolverFactory',
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

}