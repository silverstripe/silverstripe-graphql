<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder;
use SilverStripe\GraphQL\Schema\Components\Argument;
use SilverStripe\GraphQL\Schema\Components\InternalType;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class ReadOne extends ItemQueryScaffolder implements CRUDInterface
{
    /**
     * Read one constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, null, $dataObjectClass);
        $this->setResolverFactory(ReadOneResolverFactory::create([
            'dataObjectClass' => $this->getDataObjectClass()
        ]));
    }

    public function getName()
    {
        $name = parent::getName();
        if ($name) {
            return $name;
        }

        return 'readOne' . ucfirst($this->getTypeName());
    }

    /**
     * @param Manager $manager
     * @return array
     */
    protected function createDefaultArgs(Manager $manager)
    {
        return [
            new Argument(
                'ID',
                InternalType::id()->setRequired(true)
            )
        ];
    }
}
