<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Scaffolding\DataObjectTypeTrait;
use SilverStripe\GraphQL\Scaffolding\Resolvers\Delete;
use SilverStripe\ORM\DataList;

/**
 * A generic delete operation
 * @package SilverStripe\GraphQL\Scaffolding\Scaffolders
 */
class DeleteOperationScaffolder extends MutationScaffolder
{

    use DataObjectTypeTrait;

    /**
     * DeleteOperationScaffolder constructor.
     * @param null $dataObjectName
     */
    public function __construct($dataObjectName)
    {
        $this->dataObjectName = $dataObjectName;
        $this->args = [
            'ID' => 'ID!'
        ];

        parent::__construct(
            'delete' . ucfirst($this->typeName()),
            $this->typeName()
        );

        $this->setResolver(function ($object, array $args, $context, $info) {
            if (singleton($this->dataObjectName)->canDelete()) {
                $obj = DataList::create($this->dataObjectName)
                    ->byID($args['ID']);

                if ($obj) {
                    $obj->delete();
                }

                return $obj;
            } else {
                // permission error
            }
        });

    }
}