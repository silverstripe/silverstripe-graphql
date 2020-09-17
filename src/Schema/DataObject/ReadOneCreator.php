<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\ORM\DataObject;
use Closure;

/**
 * Creates a readOne (by id) query for a DataObject
 */
class ReadOneCreator implements OperationCreator
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

        return ModelQuery::create($model, 'readOne' . ucfirst($typeName))
            ->setType($typeName)
            ->setPlugins($plugins)
            ->setDefaultResolver([ReadCreator::class, 'resolve'])
            ->setResolverContext([
                'dataClass' => $model->getSourceClass()
            ]);
    }

}
