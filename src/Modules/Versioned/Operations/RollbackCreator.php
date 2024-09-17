<?php

namespace SilverStripe\GraphQL\Modules\Versioned\Operations;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Modules\Versioned\Resolvers\VersionedResolver;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Model\ModelData;

// GraphQL dependency is optional in versioned,
// and the following implementation relies on existence of this class (in GraphQL v4)
if (!interface_exists(OperationCreator::class)) {
    return;
}

/**
 * Scaffolds a "rollback recursive" operation for DataObjects.
 *
 * rollback[TypeName](ID!, ToVersion!)
 */
class RollbackCreator implements OperationCreator
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
    ): ?ModelOperation {
        if (!ModelData::has_extension($model->getSourceClass(), Versioned::class)) {
            return null;
        }

        $defaultPlugins = $this->config()->get('default_plugins');
        $configPlugins = $config['plugins'] ?? [];
        $plugins = array_merge($defaultPlugins, $configPlugins);
        $mutationName = 'rollback' . ucfirst($typeName ?? '');
        return ModelMutation::create($model, $mutationName)
            ->setPlugins($plugins)
            ->setType($typeName)
            ->setResolver([VersionedResolver::class, 'resolveRollback'])
            ->addResolverContext('dataClass', $model->getSourceClass())
            ->addArg('id', [
                'type' => 'ID!',
                'description' => 'The object ID that needs to be rolled back'
            ])
            ->addArg('toVersion', [
                'type' => 'Int!',
                'description' => 'The version of the object that should be rolled back to',
            ]);
    }
}
