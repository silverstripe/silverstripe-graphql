<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataObject;
use Closure;

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
     * @return ModelOperation
     * @throws SchemaBuilderException
     */
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): ModelOperation
    {
        $defaultPlugins = $this->config()->get('default_plugins');
        $configPlugins = $config['plugins'] ?? [];
        $plugins = array_merge($defaultPlugins, $configPlugins);

        return ModelQuery::create($model, 'readOne' . ucfirst($typeName))
            ->setType($typeName)
            ->setPlugins($plugins)
            ->setDefaultResolver([static::class, 'resolve'])
            ->setResolverContext([
                'dataClass' => $model->getSourceClass()
            ])
            ->addArg('ID', 'ID!');
    }

    /**
     * @param array $resolverContext
     * @return Closure
     */
    public static function resolve(array $resolverContext = []): Closure
    {
        $dataClass = $resolverContext['dataClass'] ?? null;
        return static function ($obj, $args = []) use ($dataClass) {
            if (!$dataClass) {
                return null;
            }
            return DataObject::get_by_id($dataClass, $args['ID']);
        };
    }

}
