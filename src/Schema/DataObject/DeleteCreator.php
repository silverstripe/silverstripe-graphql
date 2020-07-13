<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\FieldAbstraction;
use SilverStripe\GraphQL\Schema\MutationAbstraction;
use SilverStripe\GraphQL\Schema\OperationCreator;
use SilverStripe\GraphQL\Schema\PermissionsException;
use SilverStripe\GraphQL\Schema\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\SchemaUtils;
use SilverStripe\ORM\DataList;
use Closure;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

class DeleteCreator implements OperationCreator
{
    use Injectable;

    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array $config
     * @return FieldAbstraction
     */
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): FieldAbstraction
    {
        return MutationAbstraction::create(
            'delete' . ucfirst(SchemaUtils::pluralise($typeName)),
            [
                'type' => '[ID]',
                'defaultResolver' => [static::class, 'resolve'],
                'resolverContext' => [
                    'dataClass' => $model->getSourceClass()
                ],
                'args' => [
                    'IDs' => '[ID]!'
                ]
            ]
        );
    }

    /**
     * @param array $resolverContext
     * @return Closure
     */
    public static function resolve(array $resolverContext = []): Closure
    {
        $dataClass = $resolverContext['dataClass'] ?? null;
        return static function ($obj, $args = [], $context = [], ResolveInfo $info) use ($dataClass) {
            if (!$dataClass) {
                return null;
            }
            $ids = [];
            DB::get_conn()->withTransaction(function () use ($args, $context, $info, $dataClass, $ids) {
                // Build list to filter
                $results = DataList::create($dataClass)
                    ->byIDs($args['IDs']);

                // Before deleting, check if any items fail canDelete()
                /** @var DataObject[] $resultsList */
                $resultsList = $results->toArray();
                foreach ($resultsList as $obj) {
                    if (!$obj->canDelete($context['currentUser'])) {
                        throw new PermissionsException(sprintf(
                            'Cannot delete %s with ID %s',
                            $dataClass,
                            $obj->ID
                        ));
                    }
                }

                // Delete
                foreach ($resultsList as $obj) {
                    $obj->delete();
                    $ids[] = $obj->OldID;
                }
            });

            return $ids;
        };
    }


}
