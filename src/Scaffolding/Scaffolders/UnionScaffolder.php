<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Exception;
use GraphQL\Type\Definition\UnionType;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Resolvers\UnionResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Serialisation\SerialisableUnionType;
use SilverStripe\ORM\DataObject;

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
     */
    public function scaffold(Manager $manager)
    {
        $types = $this->types;
        return SerialisableUnionType::create([
            'name' => $this->name,
            'types' => function () use ($manager, $types) {
                return array_filter(
                    array_map(function ($item) use ($manager) {
                        return $manager->hasType($item)? $manager->getType($item) : null;
                    }, $types)
                );
            },
            'resolveType' => new UnionResolverFactory($types)
        ]);
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $manager->addType($this->scaffold($manager));
    }

}
