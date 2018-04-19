<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class ReadOne extends ItemQueryScaffolder implements ResolverInterface, CRUDInterface
{
    /**
     * Read one constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, $this, $dataObjectClass);
    }

    /**
     * @return string
     */
    public function getDefaultName()
    {
        $typeName = $this->getDataObjectInstance()->singular_name();
        $typeName = str_replace(' ', '', $typeName);
        $typeName = ucfirst($typeName);
        return 'readOne' . $typeName;
    }

    /**
     * @param Manager $manager
     * @return array
     */
    protected function createDefaultArgs(Manager $manager)
    {
        return [
            'ID' => [
                'type' => Type::nonNull(Type::id())
            ]
        ];
    }

    /**
     * @param DataObjectInterface $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws Exception
     */
    public function resolve($object, $args, $context, $info)
    {
        if (!singleton($this->dataObjectClass)->canView($context['currentUser'])) {
            throw new Exception(sprintf(
                'Cannot view %s',
                $this->dataObjectClass
            ));
        }
        // get as a list so extensions can influence it pre-query
        $list = DataList::create($this->dataObjectClass)
            ->filter('ID', $args['ID']);
        $this->extend('updateList', $list, $args, $context, $info);

        return $list->first();
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        if (!$this->operationName) {
            $this->setName($this->getDefaultName());
        }
        parent::addToManager($manager);
    }
}
