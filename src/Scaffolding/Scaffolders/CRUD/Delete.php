<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Schema\Components\Argument;
use SilverStripe\GraphQL\Schema\Components\InternalType;

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
        $this->setResolver(DeleteResolverFactory::create([
            'dataObjectClass' => $this->getDataObjectClass()
        ]));
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
            new Argument(
                'IDs',
                InternalType::id()
                    ->setList(true)
                    ->setRequired(true)
            ),
        ];
    }
}
