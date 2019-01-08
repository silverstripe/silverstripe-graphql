<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\UnionType;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Resolvers\UnionResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\GraphQL\Storage\Encode\UnionTypeFactory;
use Psr\Container\NotFoundExceptionInterface;

class UnionScaffolder implements ScaffolderInterface, ManagerMutatorInterface
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $types = [];

    /**
     * @param string $name
     * @param array  $types
     */
    public function __construct($name, $types = [])
    {
        $this->name = $name;
        $this->types = $types;
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
     * @return UnionScaffolder
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param array $types
     * @return $this
     */
    public function setTypes($types)
    {
        $this->types = $types;

        return $this;
    }

    /**
     * @param Manager $manager
     * @return UnionType
     * @throws NotFoundExceptionInterface
     */
    public function scaffold(Manager $manager)
    {
        $types = $this->types;
        $typeFactory = new UnionTypeFactory(['types' => $types]);
        $resolverFactory = new UnionResolverFactory();
        return new UnionType([
            'name' => $this->name,
            'types' => $typeFactory->createClosure($manager),
            'resolveType' => $resolverFactory->createClosure($manager),
            'typesFactory' => $typeFactory,
            'resolveTypeFactory' => $resolverFactory,
        ]);
    }

    /**
     * @param Manager $manager
     * @throws NotFoundExceptionInterface
     */
    public function addToManager(Manager $manager)
    {
        $manager->addType($this->scaffold($manager));
    }

}
