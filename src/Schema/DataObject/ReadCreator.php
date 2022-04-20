<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\ORM\DataList;
use Closure;

/**
 * Creates a read operation for a DataObject
 */
class ReadCreator implements OperationCreator
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
        $queryName = $config['name'] ?? null;
        $resolver = $config['resolver'] ?? null;

        if (!$queryName) {
            $pluraliser = $model->getSchemaConfig()->getPluraliser();
            $suffix = $pluraliser ? $pluraliser($typeName) : $typeName;
            $queryName = 'read' . ucfirst($suffix ?? '');
        }

        $query = ModelQuery::create($model, $queryName)
            ->setType("[$typeName!]!")
            ->setPlugins($plugins)
            ->setResolver([static::class, 'resolve'])
            ->setResolverContext([
                'dataClass' => $model->getSourceClass(),
            ]);
        if ($resolver) {
            $query->setResolver($resolver);
        }

        return $query;
    }

    /**
     * @param array $resolverContext
     * @return Closure
     */
    public static function resolve(array $resolverContext = []): Closure
    {
        $dataClass = $resolverContext['dataClass'] ?? null;
        return function () use ($dataClass) {
            if (!$dataClass) {
                return null;
            }
            return DataList::create($dataClass);
        };
    }
}
