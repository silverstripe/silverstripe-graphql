<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Exception;
use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Scaffolding\Traits\Chainable;
use SilverStripe\ORM\ArrayList;

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
    private $typeName;

    /**
     * Name of operation
     *
     * @var string
     */
    private $operationName;

    /**
     * @var OperationResolver|callable
     */
    private $resolver;

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
     * @param OperationResolver|callable|null $resolver
     */
    public function __construct($operationName = null, $typeName = null, $resolver = null)
    {
        $this->setName($operationName);
        $this->setTypeName($typeName);
        $this->args = ArrayList::create([]);

        if ($resolver) {
            $this->setResolver($resolver);
        }
    }

    /**
     * Adds visible fields, and optional descriptions.
     *
     * Ex:
     * [
     *    'MyField' => 'Some description',
     *    'MyOtherField' // No description
     * ]
     *
     * @param array $argData
     * @return $this
     */
    public function addArgs(array $argData)
    {
        foreach ($argData as $argName => $typeStr) {
            $this->removeArg($argName);
            $this->args->add(new ArgumentScaffolder($argName, $typeStr));
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
     * @param callable|OperationResolver|string $resolver Callable, instance of (or classname of) a OperationResolver
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setResolver($resolver)
    {
        if (is_callable($resolver) || $resolver instanceof OperationResolver) {
            $this->resolver = $resolver;
            return $this;
        }
        if (is_subclass_of($resolver, OperationResolver::class)) {
            $this->resolver = Injector::inst()->create($resolver);
            return $this;
        }

        throw new InvalidArgumentException(sprintf(
            '%s::setResolver() accepts closures, instances of %s or names of resolver subclasses.',
            __CLASS__,
            OperationResolver::class
        ));
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
        if (isset($config['name'])) {
            $this->setName($config['name']);
        }

        return $this;
    }

    /**
     * Based on the type of resolver, create a function that invokes it.
     *
     * @return callable
     */
    protected function createResolverFunction()
    {
        $resolver = $this->resolver;

        return function () use ($resolver) {
            $args = func_get_args();
            if (is_callable($resolver)) {
                return call_user_func_array($resolver, $args);
            } else {
                if ($resolver instanceof OperationResolver) {
                    return call_user_func_array([$resolver, 'resolve'], $args);
                } else {
                    throw new \Exception(sprintf(
                        '%s resolver must be a closure or implement %s',
                        __CLASS__,
                        OperationResolver::class
                    ));
                }
            }
        };
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
            $args[$scaffolder->argName] = $scaffolder->toArray();
        }
        $this->extend('updateArgs', $args, $manager);
        return $args;
    }
}
