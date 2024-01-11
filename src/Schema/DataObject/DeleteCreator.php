<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Exception\PermissionsException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\ORM\DataList;
use Closure;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * Creates a delete operation for a DataObject
 */
class DeleteCreator implements OperationCreator
{
    use Injectable;
    use Configurable;

    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array $config
     * @return ModelOperation|null
     * @throws SchemaBuilderException
     */
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): ?ModelOperation {
        $plugins = $config['plugins'] ?? [];
        $mutationName = $config['name'] ?? null;
        $idField = $config['idField'] ?? 'id';
        if (!$mutationName) {
            $pluraliser = $model->getSchemaConfig()->getPluraliser();
            $suffix = $pluraliser ? $pluraliser($typeName) : $typeName;
            $mutationName = 'delete' . ucfirst($suffix ?? '');
        }

        return ModelMutation::create($model, $mutationName)
            ->setType('[ID]')
            ->setPlugins($plugins)
            ->setResolver([static::class, 'resolve'])
            ->setResolverContext([
                'dataClass' => $model->getSourceClass(),
                'idField' => $idField,
            ])
            ->addArg('ids', '[ID]!');
    }

    /**
     * @param array $resolverContext
     * @return Closure
     */
    public static function resolve(array $resolverContext = []): Closure
    {
        $dataClass = $resolverContext['dataClass'] ?? null;
        $idField = $resolverContext['idField'] ?? 'id';
        return function ($obj, array $args, array $context, ResolveInfo $info) use ($dataClass, $idField) {
            if (!$dataClass) {
                return null;
            }
            $ids = [];
            DB::get_conn()->withTransaction(function () use ($args, $context, $info, $dataClass, $ids, $idField) {
                // Build list to filter
                if (strtolower($idField ?? '') === 'id') {
                    $results = DataList::create($dataClass)
                        ->byIDs($args['ids']);
                } else {
                    $results = DataList::create($dataClass)
                        ->filter($idField, $args['ids']);
                }

                // Before deleting, check if any items fail canDelete()
                $resultsList = $results->toArray();
                foreach ($resultsList as $obj) {
                    $member = UserContextProvider::get($context);
                    if (!$obj->canDelete($member)) {
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
                    $ids[] = $obj;
                }
            });

            return $ids;
        };
    }
}
