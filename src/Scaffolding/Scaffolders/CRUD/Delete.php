<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;

/**
 * A generic delete operation.
 */
class Delete extends MutationScaffolder implements CRUDInterface
{
    /**
     * Delete constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, null, $dataObjectClass);
        $this->setResolverFactory(DeleteResolverFactory::create($this->getDataObjectClass()));

    }

    /**
     * @return string
     */
    public function getName()
    {
        $name = parent::getName();
        if ($name) {
            return $name;
        }

        return 'delete' . ucfirst($this->getTypeName());
    }

    /**
     * @param Manager $manager
     * @return array
     */
    protected function createDefaultArgs(Manager $manager)
    {
        return [
            'IDs' => [
                'type' => Type::nonNull(Type::listOf(Type::id())),
            ],
        ];
    }

}
