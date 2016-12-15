<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Scaffolding\DataObjectTypeTrait;
use SilverStripe\GraphQL\Scaffolding\Resolvers\Read;
use SilverStripe\ORM\DataList;

/**
 * Scaffolds a generic read operation for DataObjects
 */
class ReadOperationScaffolder extends QueryScaffolder
{

    use DataObjectTypeTrait;

    /**
     * ReadOperationScaffolder constructor.
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        // default args of some sort
        $this->args = [
            'Limit' => 'Int=20',
            'Sort' => 'String'
        ];

        $typeName = $this->getDataObjectInstance()->plural_name();
        $typeName = str_replace(' ', '', $typeName);
        $typeName = ucfirst($typeName);
        $operationName = 'read' . $typeName;

        parent::__construct($operationName, $this->typeName());

        $this->setResolver(function ($object, array $args, $context, $info) {
            $list = DataList::create($this->dataObjectClass);
            $list = $list->limit($args['Limit']);
            if (isset($args['Sort'])) {
                $list = $list->sort($args['Sort']);
            }

            return $list->filterbyCallback(function ($item, $list) {
            	return $item->canView();
            });
        });

    }
}