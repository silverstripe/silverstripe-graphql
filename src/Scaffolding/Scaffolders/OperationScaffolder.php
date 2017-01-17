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
use SilverStripe\ORM\ArrayList;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ArgumentScaffolder;
use Exception;

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
            case SchemaScaffolder::CREATE:
                return Create::class;
            case SchemaScaffolder::READ:
                return Read::class;
            case SchemaScaffolder::UPDATE:
                return Update::class;
            case SchemaScaffolder::DELETE:
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
     * @param array $fields
     */
    public function addArgs(array $argData)
    {
        $args = [];
        foreach($argData as $argName => $typeStr) {
        	$this->removeArg($argName);
        	$this->args->add(new ArgumentScaffolder($argName, $typeStr));
        }

        return $this;
    }

    /**
     * @param $field
     * @param  $description
     *
     * @return mixed
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
     * 	'Email' => 'The email of the user'
     * ]
     * @param array $argData 
     * @return  $this
     */
    public function setArgDescriptions(array $argData)
    {
    	foreach($argData as $argName => $description) {
    		$arg = $this->args->find('argName', $argName);
    		if(!$arg) {
    			throw new InvalidArgumentException(sprintf(
    				'Tried to set description for %s, but it was not added to %s',
    				$argName,
    				$this->operationName
    			));
    		}

    		$arg->setDescription($description);
    	}

    	return $this;
    }

    /**
     * Sets a single arg description
     * @param string $argName     
     * @param string $description 
     */
    public function setArgDescription($argName, $description)
    {
    	return $this->setArgDescriptions([$argName => $description]);
    }

    /**
     * Sets argument defaults
     * [
     * 	'Featured' => true
     * ]
     * @param array $argData
     * @return  $this
     */
    public function setArgDefaults(array $argData)
    {
    	foreach($argData as $argName => $default) {
    		$arg = $this->args->find('argName', $argName);
    		if(!$arg) {
    			throw new InvalidArgumentException(sprintf(
    				'Tried to set default for %s, but it was not added to %s',
    				$argName,
    				$this->operationName
    			));
    		}

    		$arg->setDefaultValue($default);
    	}

    	return $this;    	
    }

    /**
     * Sets a default for a single arg
     * @param string $argName 
     * @param mixed $default 
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
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param $arg
     *
     * @return $this
     */
    public function removeArg($arg)
    {
        return $this->removeArgs([$arg]);
    }

    /**
     * @param array $fields
     *
     * @return $this
     */
    public function removeArgs(array $args)
    {
        $this->args = $this->args->exclude('argName', $args);

        return $this;
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
        	if(!is_array($config['args'])) {
        		throw new Exception(sprintf(
        			'args must be an array on %s',
        			$this->operationName
        		));
        	}
        	foreach($config['args'] as $argName => $argData) {
        		if(is_array($argData)) {
					if(!isset($argData['type'])) {
						throw new Exception(sprintf(
							'Argument %s must have a type',
							$argName
						));
					}

        			$scaffolder = new ArgumentScaffolder($argName, $argData['type']);
        			$scaffolder->applyConfig($argData);
        			$this->removeArg($argName);
        			$this->args->add($scaffolder);
        		} else if(is_string($argData)) {
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
        $args = [];
        foreach($this->args as $scaffolder) {
        	$args[$scaffolder->argName] = $scaffolder->toArray();
        }

        return $args;
    }
}
