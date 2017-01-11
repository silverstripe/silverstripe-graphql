<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Util\ArgsParser;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Traits\Chainable;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Update;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Delete;
use SilverStripe\GraphQL\Scaffolding\Interfaces\Configurable;

/**
 * Provides functionality common to both operation scaffolders. Cannot
 * be a subclass due to their distinct inheritance chains.
 */
abstract class OperationScaffolder implements Configurable
{
    use Chainable;

    /**
     * @var string
     */
    protected $typeName;

    /**
     * @var string
     */
    protected $operationName;

    /**
     * @var \Closure|SilverStripe\GraphQL\ResolverInterface
     */
    protected $resolver;

    /**
     * @var array
     */
    protected $args = [];

    /**
     * @param $name
     */
    public static function getOperationScaffoldFromIdentifier($name)
    {
        switch ($name) {
            case GraphQLScaffolder::CREATE:
                return Create::class;
            case GraphQLScaffolder::READ:
                return Read::class;
            case GraphQLScaffolder::UPDATE:
                return Update::class;
            case GraphQLScaffolder::DELETE:
                return Delete::class;
        }

        return null;
    }

    /**
     * OperationScaffolder constructor.
     *
     * @param null $operationName
     * @param null $resolver      Resolver|\Closure
     */
    public function __construct($operationName, $typeName, $resolver = null)
    {
        $this->operationName = $operationName;
        $this->typeName = $typeName;

        if ($resolver) {
            $this->setResolver($resolver);
        }
    }

    /**
     * Adds arguments to the mutation. e.g.
     * [
     *    'Email' => 'String!', // required
     *    'Limit' => 'Int=10', // optional, default value of 10
     *    'Featured' => 'Boolean'
     * ].
     *
     * @param array $args
     *
     * @return $this
     */
    public function addArgs($args = [])
    {
        $this->args = array_merge($this->args, $args);

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->operationName;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param $resolver
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setResolver($resolver)
    {
        if (is_callable($resolver) || $resolver instanceof ResolverInterface) {
            $this->resolver = $resolver;
        } else {
            if (is_subclass_of($resolver, ResolverInterface::class)) {
                $this->resolver = Injector::inst()->create($resolver);
            } else {
                throw new InvalidArgumentException(sprintf(
                    '%s::setResolver() accepts closures, instances of %s or names of resolver subclasses.',
                    __CLASS__,
                    ResolverInterface::class
                ));
            }
        }

        return $this;
    }

    /**
     * @param array $config
     *
     * @return OperationScaffolder
     */
    public function applyConfig(array $config)
    {
        if (isset($config['args'])) {
            $this->addArgs($config['args']);
        }
        if (isset($config['resolver'])) {
            $this->setResolver($config['resolver']);
        }

        return $this;
    }

    /**
     * Based on the type of resolver, create a function that invokes it.
     *
     * @return Closure
     */
    protected function createResolverFunction()
    {
        $resolver = $this->resolver;

        return function () use ($resolver) {
            $args = func_get_args();
            if (is_callable($resolver)) {
                return call_user_func_array($resolver, $args);
            } else {
                if ($resolver instanceof ResolverInterface) {
                    return call_user_func_array([$resolver, 'resolve'], $args);
                } else {
                    throw new \Exception(sprintf(
                        '%s resolver must be a closure or implement %s',
                        __CLASS__,
                        ResolverInterface::class
                    ));
                }
            }
        };
    }

    /**
     * Parses the args to proper graphql-php spec.
     *
     * @return array
     */
    protected function createArgs()
    {
        return (new ArgsParser($this->args))->toArray();
    }
}
