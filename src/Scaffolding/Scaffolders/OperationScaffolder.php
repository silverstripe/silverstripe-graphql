<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Util\ArgsParser;
use SilverStripe\GraphQL\Scaffolding\ResolverInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * Trait Operation
 * @package SilverStripe\GraphQL\Scaffolding\Operations
 */
abstract class OperationScaffolder
{
    /**
     * @var string
     */
    protected $typeName;

    /**
     * @var string
     */
    protected $operationName;


    /**
     * @var \Closure | SilverStripe\GraphQL\Scaffolding\ResolverInterface
     */
    protected $resolver;

    /**
     * @var array
     */
    protected $args = [];


    /**
     * @param array $config
     * @return OperationScaffolder
     * @throws InvalidArgumentException
     */
    public static function createFromConfig($typeName, $config)
    {
        if (!isset($config['name'])) {
            throw new InvalidArgumentException(sprintf(
                '%s::%s() config array must have a key for "name"',
                __CLASS__,
                __FUNCTION__
            ));
        }

        $operation = new static($config['name'], $typeName);

        if (isset($config['resolver'])) {
            $operation->setResolver($config['resolver']);
        }

        return $operation;
    }


    /**
     * OperationScaffolder constructor.
     * @param null $operationName
     * @param  null $resolver Resolver|\Closure
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
     * ]
     * @param array $args
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
     * @param $resolver
     * @return $this
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
                    "%s::setResolver() accepts closures, instances of %s or names of resolver subclasses.",
                    __CLASS__,
                    ResolverInterface::class
                ));
            }
        }

        return $this;
    }

    /**
     * Parses the args to proper graphql-php spec
     * @return array
     */
    protected function createArgs()
    {
        return (new ArgsParser($this->args))->toArray();
    }

}