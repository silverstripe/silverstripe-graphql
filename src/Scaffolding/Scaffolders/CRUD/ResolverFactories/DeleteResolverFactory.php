<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Storage\Encode\TypeRegistryInterface;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Exception;
use Closure;

class DeleteResolverFactory extends CRUDResolverFactory
{

    /**
     * @param TypeRegistryInterface $registry
     * @return callable|Closure
     */
    public function createResolver(TypeRegistryInterface $registry)
    {
        $class = $this->getDataObjectClass();

        return function ($object, array $args, $context, ResolveInfo $info) use ($class) {
            DB::get_conn()->withTransaction(function () use ($args, $context, $class) {
                // Build list to filter
                $results = DataList::create($class)
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
                            $class,
                            $obj->ID
                        ));
                    }
                }

                // Delete
                foreach ($resultsList as $obj) {
                    $obj->delete();
                }
            });
        };
    }
}