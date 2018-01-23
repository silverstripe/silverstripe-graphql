<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\Type;
use SilverStripe\ORM\DataList;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder;
use SilverStripe\GraphQL\Manager;
use Exception;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class ReadOne extends ItemQueryScaffolder
{
    /**
     * ReadOperationScaffolder constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        $typeName = $this->getDataObjectInstance()->singular_name();
        $typeName = str_replace(' ', '', $typeName);
        $typeName = ucfirst($typeName);
        $operationName = 'readOne'.$typeName;

        $resolver = function ($object, array $args, $context, $info) {
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
        };

        parent::__construct($operationName, $this->typeName(), $resolver);
    }

    /**
     * @param Manager $manager
     * @return array
     */
    protected function createArgs(Manager $manager)
    {
        $args = [
            'ID' => Type::nonNull(Type::id()),
        ];
        $this->extend('updateArgs', $args, $manager);

        return $args;
    }
}
