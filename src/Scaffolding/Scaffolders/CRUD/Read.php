<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Security\Member;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class Read extends ListQueryScaffolder implements OperationResolver, CRUDInterface
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
        return DataList::create($this->getDataObjectClass());
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

        $typePlural = $this->pluralise($this->getResolvedTypeName());
        return 'read' . ucfirst($typePlural);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    protected function checkPermission(Member $member = null)
    {
        return $this->getDataObjectInstance()->canView($member);
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
        if (!$this->checkPermission($context['currentUser'])) {
            throw new Exception(sprintf(
                'Cannot view %s',
                $this->getDataObjectClass()
            ));
        }

        $list = $this->getResults($args);
        $this->extend('updateList', $list, $args, $context, $info);
        return $list;
    }

    /**
     * Pluralise a name
     *
     * @param string $typeName
     * @return string
     */
    protected function pluralise($typeName)
    {
        // Ported from DataObject::plural_name()
        if (preg_match('/[^aeiou]y$/i', $typeName)) {
            $typeName = substr($typeName, 0, -1) . 'ie';
        }
        $typeName .= 's';
        return $typeName;
    }
}
