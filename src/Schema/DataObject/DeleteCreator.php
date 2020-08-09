<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Exception\PermissionsException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Schema;
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
     * @var array
     * @config
     */
    private static $default_plugins = [];

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
    ): ?ModelOperation
    {
        $defaultPlugins = $this->config()->get('default_plugins');
        $configPlugins = $config['plugins'] ?? [];
        $plugins = array_merge($defaultPlugins, $configPlugins);
        $mutationName = 'delete' . ucfirst(Schema::pluralise($typeName));

        return ModelMutation::create($model, $mutationName)
            ->setType('[ID]')
            ->setPlugins($plugins)
            ->setDefaultResolver([static::class, 'resolve'])
            ->setResolverContext([
                'dataClass' => $model->getSourceClass(),
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
        return function ($obj, array $args, array $context, ResolveInfo $info) use ($dataClass) {
            if (!$dataClass) {
                return null;
            }
            $ids = [];
            DB::get_conn()->withTransaction(function () use ($args, $context, $info, $dataClass, $ids) {
                // Build list to filter
                $results = DataList::create($dataClass)
                    ->byIDs($args['ids']);

                // Before deleting, check if any items fail canDelete()
                /** @var DataObject[] $resultsList */
                $resultsList = $results->toArray();
                foreach ($resultsList as $obj) {
                    if (!$obj->canDelete($context[QueryHandler::CURRENT_USER])) {
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
