<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Security\Member;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class Read extends ListQueryScaffolder implements ResolverInterface, CRUDInterface
{
    /**
     * Read constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, $this, $dataObjectClass);
    }

    /**
     * @param array $args
     * @return DataList
     */
    protected function getResults($args)
    {
        return DataList::create($this->dataObjectClass);
    }

    /**
     *
     * @return string
     */
    public function getDefaultName()
    {
        $typeName = $this->typeName();

        // Ported from DataObject::plural_name()
        if (preg_match('/[^aeiou]y$/i', $typeName)) {
            $typeName = substr($typeName, 0, -1) . 'ie';
        }
        return 'read' . ucfirst($typeName . 's');
    }

    /**
     * @param Member $member
     * @return boolean
     */
    protected function checkPermission(Member $member)
    {
        return singleton($this->dataObjectClass)->canView($member);
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
        if (!$this->checkPermission($context['currentUser'])) {
            throw new Exception(sprintf(
                'Cannot view %s',
                $this->dataObjectClass
            ));
        }

        $list = $this->getResults($args);
        $this->extend('updateList', $list, $args, $context, $info);
        return $list;
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
