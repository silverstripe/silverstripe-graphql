<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * A generic delete operation.
 */
class Delete extends MutationScaffolder implements OperationResolver, CRUDInterface
{
    /**
     * Delete constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, $this, $dataObjectClass);
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

        return 'delete' . ucfirst($this->getResolvedTypeName());
    }

    /**
     * @param Manager $manager
     * @return array
     */
    protected function createDefaultArgs(Manager $manager)
    {
        return [
            'IDs' => [
                'type' => Type::nonNull($this->generateInputType()),
            ],
        ];
    }

    /**
     * @return ListOfType
     */
    protected function generateInputType()
    {
        return Type::listOf(Type::id());
    }

    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        DB::get_conn()->withTransaction(function () use ($args, $context) {
            // Build list to filter
            $results = DataList::create($this->getDataObjectClass())
                ->byIDs($args['IDs']);
            $extensionResults = $this->extend('augmentMutation', $results, $args, $context, $info);

            // Extension points that return false should kill the deletion
            if (in_array(false, $extensionResults, true)) {
                return;
            }

            // Before deleting, check if any items fail canDelete()
            /** @var DataObject[] $resultsList */
            $resultsList = $results->toArray();
            foreach ($resultsList as $obj) {
                if (!$obj->canDelete($context['currentUser'])) {
                    throw new Exception(sprintf(
                        'Cannot delete %s with ID %s',
                        $this->getDataObjectClass(),
                        $obj->ID
                    ));
                }
            }

            // Delete
            foreach ($resultsList as $obj) {
                $obj->delete();
            }
        });
    }
}
