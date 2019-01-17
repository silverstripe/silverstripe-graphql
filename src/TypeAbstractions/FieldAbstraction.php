<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use SilverStripe\GraphQL\Storage\Encode\ClosureFactoryInterface;

class FieldAbstraction
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
     * @var TypeAbstraction
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
     * FieldAbstraction constructor.
     * @param string $name
     * @param TypeAbstraction $type
     * @param ResolverAbstraction $resolver
     * @param array $args
     */
    public function __construct(
        $name,
        TypeAbstraction $type,
        ResolverAbstraction $resolver = null,
        $args = []
    ) {
        $this->setName($name)
            ->setType($type)
            ->setResolver($resolver)
            ->setArgs($args)
            ->setDescription($description);
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
     * @param ResolverAbstraction $resolverCallable
     * @return FieldAbstraction
     */
    public function setResolver(ResolverAbstraction $resolver)
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
     * @return TypeAbstraction
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param TypeAbstraction $type
     * @return $this
     */
    public function setType(TypeAbstraction $type)
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
}