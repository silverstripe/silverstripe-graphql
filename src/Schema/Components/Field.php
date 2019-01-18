<?php


namespace SilverStripe\GraphQL\Schema\Components;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;

class Field implements ConfigurationApplier
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var TypeReference
     */
    protected $type;

    /**
     * @var AbstractFunction
     */
    protected $resolver;

    /**
     * @var Argument[]
     */
    protected $args = [];

    /**
     * @var string
     */
    protected $deprecationReason;

    /**
     * @param $name
     * @param mixed $type
     * @param AbstractFunction $resolver
     * @param array $args
     * @return Field
     */
    public static function create($name, $type, AbstractFunction $resolver = null, $args = [])
    {
        return new static($name, $type, $resolver, $args);
    }

    /**
     * @param array $config
     * @return Field
     */
    public static function createFromConfig(array $config)
    {
        if (!isset($config['name']) || !isset($config['type'])) {
            throw new InvalidArgumentException(sprintf(
                '%s::%s requires "name" and "type" settings',
                __CLASS__,
                __FUNCTION__
            ));
        }
        $inst = new static($config['name'], $config['type']);
        $inst->applyConfig($config);

        return $inst;
    }

    /**
     * Field constructor.
     * @param string $name
     * @param mixed $type
     * @param AbstractFunction $resolver
     * @param array $args
     */
    public function __construct($name, $type, AbstractFunction $resolver = null, $args = [])
    {
        if ($type instanceof TypeReference) {
            $ref = $type;
        } elseif ($type instanceof AbstractType) {
            $ref = TypeReference::create($type->getName());
        } elseif ($type instanceof Type) {
            // Deprecated. @todo convert graphql type
            $ref = TypeReference::create((string)$type);
        } else {
            $ref = TypeReference::create($type);
        }
        $this->setName($name)
            ->setType($ref)
            ->setResolver($resolver)
            ->setArgs($args);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Field
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Field
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return AbstractFunction
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * @param AbstractFunction $resolver
     * @return Field
     */
    public function setResolver(AbstractFunction $resolver = null)
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param Argument[] $args
     * @return Field
     */
    public function setArgs($args)
    {
        foreach ($args as $arg) {
            $this->addArg($arg);
        }

        return $this;
    }

    /**
     * @param Argument $arg
     * @return $this
     */
    public function addArg(Argument $arg)
    {
        $this->args[$arg->getName()] = $arg;

        return $this;
    }

    /**
     * @return TypeReference
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param TypeReference $type
     * @return $this
     */
    public function setType(TypeReference $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeprecationReason()
    {
        return $this->deprecationReason;
    }

    /**
     * @param string $deprecationReason
     * @return Field
     */
    public function setDeprecationReason($deprecationReason)
    {
        $this->deprecationReason = $deprecationReason;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'resolver' => $this->getResolver(),
            'type' => $this->getType(),
            'args' => $this->getArgs(),
            'deprecationReason' => $this->getDeprecationReason(),
        ];
    }

    /**
     * @param array $config
     */
    public function applyConfig(array $config)
    {
        if (isset($config['resolver'])) {
            $this->setResolver($config['resolver']);
        }
        if (isset($config['args'])) {
            $this->setArgs($config['args']);
        }
        if (isset($config['description'])) {
            $this->setDescription($config['description']);
        }
        if (isset($config['deprecationReason'])) {
            $this->setDeprecationReason($config['deprecationReason']);
        }
    }
}
