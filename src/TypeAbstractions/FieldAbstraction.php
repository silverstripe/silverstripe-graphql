<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Components\ArgumentAbstraction;

class FieldAbstraction implements ConfigurationApplier
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
     * @var ResolverAbstraction
     */
    protected $resolver;

    /**
     * @var ArgumentAbstraction[]
     */
    protected $args = [];

    /**
     * @var string
     */
    protected $deprecationReason;

    /**
     * @param $name
     * @param mixed $type
     * @param ResolverAbstraction $resolver
     * @param array $args
     * @return FieldAbstraction
     */
    public static function create($name, $type, ResolverAbstraction $resolver = null, $args = [])
    {
        return new static($name, $type, $resolver, $args);
    }

    /**
     * @param array $config
     * @return FieldAbstraction
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
     * FieldAbstraction constructor.
     * @param string $name
     * @param mixed $type
     * @param ResolverAbstraction $resolver
     * @param array $args
     */
    public function __construct($name, $type, ResolverAbstraction $resolver = null, $args = []) {
        if ($type instanceof TypeAbstraction) {
            $ref = TypeReference::create($type->getName());
        } else if ($type instanceof Type) {
            // Deprecated. @todo convert graphql type
            $ref = TypeReference::create((string) $type);
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
     * @return FieldAbstraction
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
     * @return FieldAbstraction
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return ResolverAbstraction
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * @param ResolverAbstraction $resolver
     * @return FieldAbstraction
     */
    public function setResolver(ResolverAbstraction $resolver = null)
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
     * @param ArgumentAbstraction[] $args
     * @return FieldAbstraction
     */
    public function setArgs($args)
    {
        foreach ($args as $arg) {
            $this->addArg($arg);
        }

        return $this;
    }

    /**
     * @param ArgumentAbstraction $arg
     * @return $this
     */
    public function addArg(ArgumentAbstraction $arg)
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
     * @return FieldAbstraction
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