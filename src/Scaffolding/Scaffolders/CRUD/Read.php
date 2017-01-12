<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\GraphQL\Scaffolding\Scaffolders\QueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\ORM\DataList;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Exception;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class Read extends QueryScaffolder implements CRUDInterface
{
    use DataObjectTypeTrait;

    /**
     * ReadOperationScaffolder constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        $typeName = $this->getDataObjectInstance()->plural_name();
        $typeName = str_replace(' ', '', $typeName);
        $typeName = ucfirst($typeName);
        $operationName = 'read'.$typeName;

        parent::__construct($operationName, $this->typeName());

        $this->setResolver(function ($object, array $args, $context, $info) {
            if(!singleton($this->dataObjectClass)->canView($context['currentMember'])) {
            	throw new Exception(sprintf(
            		'Cannot create %s',
            		$this->dataObjectClass
            	));
            }
            
            $list = DataList::create($this->dataObjectClass);

            return $list;
        });
    }

    /**
     * @return string`
     */
    public function getIdentifier()
    {
        return SchemaScaffolder::READ;
    }
}
