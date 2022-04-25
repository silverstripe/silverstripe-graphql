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
 * Creates a readOne query for a DataObject
 */
class ReadOneCreator implements OperationCreator
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
        if (!$queryName) {
            $queryName = 'readOne' . ucfirst($typeName ?? '');
        }
        return ModelQuery::create($model, $queryName)
            ->setType($typeName)
            ->setPlugins($plugins)
            ->setResolver([ReadCreator::class, 'resolve'])
            ->setResolverContext([
                'dataClass' => $model->getSourceClass()
            ]);
    }
}
