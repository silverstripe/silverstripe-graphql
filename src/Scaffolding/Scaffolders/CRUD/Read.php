<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;
use SilverStripe\ORM\DataList;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\UnionScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Manager;
use SilverStripe\Core\ClassInfo;
use Exception;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Security\Member;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class Read extends ListQueryScaffolder implements ResolverInterface
{
    /**
     * ReadOperationScaffolder constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;
        parent::__construct(
            $this->createOperationName(),
            $this->typeName(),
            $this,
            $dataObjectClass
        );
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
    protected function createOperationName()
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
}
