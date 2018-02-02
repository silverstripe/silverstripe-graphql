<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\Type;
use SilverStripe\ORM\DataList;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\UnionScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Util\ScaffoldingUtil;
use SilverStripe\GraphQL\Manager;
use SilverStripe\Core\ClassInfo;
use Exception;
use SilverStripe\Security\Member;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class Read extends ListQueryScaffolder
{
    /**
     * ReadOperationScaffolder constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;
        $operationName = $this->createOperationName();

        $resolver = function ($object, array $args, $context, $info) {
            if (!$this->checkPermission($context['currentUser'])) {
                throw new Exception(sprintf(
                    'Cannot view %s',
                    $this->dataObjectClass
                ));
            }

            $list = $this->getResults($args);
            $this->extend('updateList', $list, $args, $context, $info);

            return $list;
        };
        
        parent::__construct($operationName, $this->typeName(), $resolver);
    }

    /**
     * @param Manager $manager
     * @return array
     */
    protected function createArgs(Manager $manager)
    {
        $args = [];
        $this->extend('updateArgs', $args, $manager);

        return $args;
    }

    /**
     * Creates a thunk that lazily fetches the type
     * @param  Manager $manager
     * @return Type
     */
    protected function getType(Manager $manager)
    {
        // Create unions for exposed descendants
        $descendants = ClassInfo::subclassesFor($this->dataObjectClass);
        array_shift($descendants);
        $union = [$this->typeName];
        foreach ($descendants as $descendant) {
            $typeName = ScaffoldingUtil::typeNameForDataObject($descendant);
            if ($manager->hasType($typeName)) {
                $union[] = $typeName;
            }
        }
        if (sizeof($union) > 1) {
            return (new UnionScaffolder(
                $this->typeName.'WithDescendants',
                $union
            ))->scaffold($manager);
        }

        return $manager->getType($this->typeName);
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
     * @return string
     */
    protected function createOperationName()
    {
        $typeName = $this->getDataObjectInstance()->plural_name();
        $typeName = str_replace(' ', '', $typeName);
        $typeName = ucfirst($typeName);
        return 'read' . $typeName;
    }

    /**
     * @param Member $member
     * @return boolean
     */
    protected function checkPermission(Member $member)
    {
        return singleton($this->dataObjectClass)->canView($member);
    }
}
