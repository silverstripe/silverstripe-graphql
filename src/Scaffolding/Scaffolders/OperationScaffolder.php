<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Exception;
use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extensible;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Components\AbstractFunction;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface;
use SilverStripe\GraphQL\Scaffolding\Traits\Chainable;
use SilverStripe\GraphQL\Schema\Components\DynamicResolver;
use SilverStripe\GraphQL\Schema\Components\StaticFunction;
use SilverStripe\ORM\ArrayList;
use Closure;

/**
 * Provides functionality common to both operation scaffolders. Cannot
 * be a subclass due to their distinct inheritance chains.
 */
abstract class OperationScaffolder implements ConfigurationApplier
{
    use Chainable;
    use Extensible;

    /**
     * Type backing this operation
     *
     * @var string
     */
    protected $typeName;

    /**
     * Name of operation
     *
     * @var string
     */
    protected $operationName;

    /**
     * @var OperationResolver|callable
     */
    protected $resolver;

    /**
     * @var ClosureFactoryInterface
     */
    protected $resolverFactory;

    /**
     * List of argument scaffolders
     *
     * @var ArrayList|ArgumentScaffolder[]
     */
    protected $args = [];

    /**
     * @param string $name
     * @return  string|null
     */
    public static function getClassFromIdentifier($name)
    {
        $operations = static::getOperations();

        return isset($operations[$name]) ? $operations[$name] : null;
    }

    /**
     * @param string|OperationScaffolder $instOrClass
     * @return  string|null
     */
    public static function getIdentifier($instOrClass)
    {
        $class = ($instOrClass instanceof OperationScaffolder) ? get_class($instOrClass) : $instOrClass;
        $operations = static::getOperations();
        $operations = array_flip($operations);

        return isset($operations[$class]) ? $operations[$class] : null;
    }

    /**
     * Gets a map of operation identifiers to their classes
     * @return array
     */
    public static function getOperations()
    {
        $operations = Config::inst()->get(__CLASS__, 'operations', Config::UNINHERITED);
        $validOperations = [];
        foreach ($operations as $identifier => $class) {
            if (!$class) {
                continue;
            }
            $validOperations[$identifier] = $class;
        }

        return $validOperations;
    }

    /**
     * OperationScaffolder constructor.
     *
     * @param string $operationName
     * @param string $typeName
     * @param OperationResolver|callable|\SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface|null $resolver
     */
    public function __construct($operationName = null, $typeName = null, $resolver = null)
    {
        $this->setName($operationName);
        $this->setTypeName($typeName);
        $this->args = ArrayList::create([]);

        if ($resolver instanceof ClosureFactoryInterface) {
            $this->setResolverFactory($resolver);
        } else if ($resolver) {
            $this->setResolver($resolver);
        }
    }

    /**
     * Adds args to the operation
     *
     * Ex:
     * [
     *    'MyArg' => 'String!',
     *    'MyOtherArg' => 'Int',
     *    'MyCustomArg' => new InputObjectType([
     * ]
     *
     * @param array $argData
     * @return $this
     */
    public function addArgs(array $argData)
    {
        foreach ($argData as $argName => $type) {
            $this->removeArg($argName);
            $this->args->add(new ArgumentScaffolder($argName, $type));
        }

        return $this;
    }

    /**
     * @param string $argName
     * @param string $typeStr
     * @param string $description
     * @param mixed $defaultValue
     * @return $this
     */
    public function addArg($argName, $typeStr, $description = null, $defaultValue = null)
    {
        $this->addArgs([$argName => $typeStr]);
        $this->setArgDescription($argName, $description);
        $this->setArgDefault($argName, $defaultValue);

        return $this;
    }

    /**
     * Sets descriptions of arguments
     * [
     *  'Email' => 'The email of the user'
     * ]
     * @param array $argData
     * @return  $this
     */
    public function setArgDescriptions(array $argData)
    {
        foreach ($argData as $argName => $description) {
            /* @var ArgumentScaffolder $arg */
            $arg = $this->args->find('argName', $argName);
            if (!$arg) {
                throw new InvalidArgumentException(sprintf(
                    'Tried to set description for %s, but it was not added to %s',
                    $argName,
                    $this->operationName ?: '(unnamed operation)'
                ));
            }

            $arg->setDescription($description);
        }

        return $this;
    }

    /**
     * Sets a single arg description
     *
     * @param string $argName
     * @param string $description
     * @return $this
     */
    public function setArgDescription($argName, $description)
    {
        return $this->setArgDescriptions([$argName => $description]);
    }

    /**
     * Sets argument defaults
     * [
     *  'Featured' => true
     * ]
     * @param array $argData
     * @return  $this
     */
    public function setArgDefaults(array $argData)
    {
        foreach ($argData as $argName => $default) {
            /* @var ArgumentScaffolder $arg */
            $arg = $this->args->find('argName', $argName);
            if (!$arg) {
                throw new InvalidArgumentException(sprintf(
                    'Tried to set default for %s, but it was not added to %s',
                    $argName,
                    $this->operationName ?: '(unnamed operation)'
                ));
            }

            $arg->setDefaultValue($default);
        }

        return $this;
    }

    /**
     * Sets a default for a single arg
     *
     * @param string $argName
     * @param mixed $default
     * @return $this
     */
    public function setArgDefault($argName, $default)
    {
        return $this->setArgDefaults([$argName => $default]);
    }

    /**
     * Sets operation arguments as required or not
     * [
     *  'ID' => true
     * ]
     * @param array $argData
     * @return $this
     */
    public function setArgsRequired($argData)
    {
        foreach ($argData as $argName => $required) {
            /* @var ArgumentScaffolder $arg */
            $arg = $this->args->find('argName', $argName);
            if (!$arg) {
                throw new InvalidArgumentException(sprintf(
                    'Tried to make arg %s required, but it was not added to %s',
                    $argName,
                    $this->operationName ?: '(unnamed operation)'
                ));
            }

            $arg->setRequired($required);
        }

        return $this;
    }

    /**
     * Sets an operation argument as required or not
     *
     * @param string $argName
     * @param boolean $required
     * @return OperationScaffolder
     */
    public function setArgRequired($argName, $required)
    {
        return $this->setArgsRequired([$argName => $required]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->operationName;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->operationName = $name;

        return $this;
    }

    /**
     * @return ArrayList
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Type name
     *
     * @param string $typeName
     * @return $this
     */
    public function setTypeName($typeName)
    {
        $this->typeName = $typeName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * @param string $arg
     * @return $this
     */
    public function removeArg($arg)
    {
        return $this->removeArgs([$arg]);
    }

    /**
     * @param array $args
     * @return $this
     */
    public function removeArgs(array $args)
    {
        $this->args = $this->args->exclude('argName', $args);

        return $this;
    }

    /**
     * @return callable|OperationResolver
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * @param callable $resolver
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setResolver($resolver)
    {
        if (!is_callable($resolver) || $resolver instanceof Closure) {
            throw new InvalidArgumentException(sprintf(
                '%s::%s must be passed a callable that is not a closure',
                __CLASS__,
                __FUNCTION__
            ));
        }
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * @param ClosureFactoryInterface $factory
     * @return $this
     */
    public function setResolverFactory(ClosureFactoryInterface $factory)
    {
        $this->resolverFactory = $factory;

        return $this;
    }

    /**
     * @return \SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface
     */
    public function getResolverFactory()
    {
        return $this->resolverFactory;
    }

    /**
     * @param array $config
     * @return $this
     * @throws Exception
     */
    public function applyConfig(array $config)
    {
        if (isset($config['args'])) {
            if (!is_array($config['args'])) {
                throw new Exception(sprintf(
                    'args must be an array on %s',
                    $this->operationName ?: '(unnamed operation)'
                ));
            }
            foreach ($config['args'] as $argName => $argData) {
                if (is_array($argData)) {
                    if (!isset($argData['type'])) {
                        throw new Exception(sprintf(
                            'Argument %s must have a type',
                            $argName
                        ));
                    }

                    $scaffolder = new ArgumentScaffolder($argName, $argData['type']);
                    $scaffolder->applyConfig($argData);
                    $this->removeArg($argName);
                    $this->args->add($scaffolder);
                } elseif (is_string($argData)) {
                    $this->addArg($argName, $argData);
                } else {
                    throw new Exception(sprintf(
                        'Arg %s should be mapped to a string or an array',
                        $argName
                    ));
                }
            }
        }
        if (isset($config['resolver'])) {
            $this->setResolver($config['resolver']);
        }
        if (isset($config['resolverFactory'])) {
            $this->setResolverFactory($config['resolverFactory']);
        }
        if (isset($config['name'])) {
            $this->setName($config['name']);
        }

        return $this;
    }

    /**
     * Based on the type of resolver, create a function that invokes it.
     *
     * @return AbstractFunction
     */
    protected function createResolverAbstraction()
    {
        if ($this->resolverFactory) {
            return new DynamicResolver($this->resolverFactory);
        }

        return new StaticFunction($this->resolver);
    }

    /**
     * Helper for scaffolding args that require more work than ArgumentScaffolder::toArray()
     *
     * @param Manager $manager
     * @return array
     */
    protected function createDefaultArgs(Manager $manager)
    {
        return [];
    }

    /**
     * Parses the args to proper graphql-php spec.
     *
     * @param Manager $manager
     * @return array
     */
    protected function createArgs(Manager $manager)
    {
        $args = $this->createDefaultArgs($manager);
        foreach ($this->args as $scaffolder) {
            $args[$scaffolder->argName] = $scaffolder->scaffold($manager);
        }
        $this->extend('updateArgs', $args, $manager);
        return $args;
    }
}
