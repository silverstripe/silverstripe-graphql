<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Scaffolding\DataObjectTypeTrait;
use SilverStripe\GraphQL\Scaffolding\Resolvers\Delete;
use SilverStripe\ORM\DataList;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;
use Exception;

/**
 * A generic delete operation
 */
class DeleteOperationScaffolder extends MutationScaffolder
{

    use DataObjectTypeTrait;

    /**
     * DeleteOperationScaffolder constructor.
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        parent::__construct(
            'delete' . ucfirst($this->typeName()),
            $this->typeName()
        );

        $this->setResolver(function ($object, array $args, $context, $info) {
            $results = DataList::create($this->dataObjectClass)
                ->byIDs($args['IDs']);

            foreach($results as $obj) {
            	if($obj->canDelete()) {
            		$obj->delete();
            	} else {
            		throw new Exception(sprintf(
            			"Cannot delete %s with ID %s",
            			$this->dataObjectClass,
            			$obj->ID
            		));
            	}
            }
        });

    }

    /**
     * @return array
     */
    protected function createArgs()
    {
    	return [
    		'IDs' => [
    			'type' => Type::nonNull(Type::listOf(Type::id()))
    		]
    	];
    }	

}