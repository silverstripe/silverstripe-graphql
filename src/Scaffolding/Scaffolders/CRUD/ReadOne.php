<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class ReadOne extends ItemQueryScaffolder implements OperationResolver, CRUDInterface
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

    public function getName()
    {
        $name = parent::getName();
        if ($name) {
            return $name;
        }

        return 'readOne' . ucfirst($this->getResolvedTypeName());
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
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        if (!$this->getDataObjectInstance()->canView($context['currentUser'])) {
            throw new Exception(sprintf(
                'Cannot view %s',
                $this->getDataObjectClass()
            ));
        }
        // get as a list so extensions can influence it pre-query
        $list = DataList::create($this->getDataObjectClass())
            ->filter('ID', $args['ID']);
        $this->extend('updateList', $list, $args, $context, $info);

        return $list->first();
    }
}
