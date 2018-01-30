<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\Core\Extensible;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\ORM\DataList;
use GraphQL\Type\Definition\Type;
use Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use GraphQL\Type\Definition\ListOfType;

/**
 * A generic delete operation.
 */
class Delete extends MutationScaffolder
{
    use DataObjectTypeTrait;

    /**
     * DeleteOperationScaffolder constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;

        parent::__construct(
            'delete'.ucfirst($this->typeName()),
            $this->typeName()
        );

        $this->setResolver(function ($object, array $args, $context, $info) {
            DB::get_conn()->withTransaction(function () use ($args, $context) {
                $results = DataList::create($this->dataObjectClass)
                    ->byIDs($args['IDs']);
                $extensionResults = $this->extend('augmentMutation', $results, $args, $context, $info);
                // Extension points that return false should kill the deletion
                if (in_array(false, $extensionResults, true)) {
                    return;
                }

                foreach ($results as $obj) {
                    /** @var DataObject $obj */
                    if ($obj->canDelete($context['currentUser'])) {
                        $obj->delete();
                    } else {
                        throw new Exception(sprintf(
                            'Cannot delete %s with ID %s',
                            $this->dataObjectClass,
                            $obj->ID
                        ));
                    }
                }
            });
        });
    }

    /**
     * @param Manager $manager
     * @return array
     */
    protected function createArgs(Manager $manager)
    {
        $args = [
            'IDs' => [
                'type' => Type::nonNull($this->generateInputType()),
            ],
        ];
        $this->extend('updateArgs', $args, $manager);

        return $args;
    }

    /**
     * @return ListOfType
     */
    protected function generateInputType()
    {
        return Type::listOf(Type::id());
    }
}
