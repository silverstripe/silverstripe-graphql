<?php

namespace SilverStripe\GraphQL\Modules\Versioned\Operations;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\DB;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Modules\Versioned\Resolvers\VersionedResolver;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Model\ModelData;

// GraphQL dependency is optional in versioned,
// and the following implementation relies on existence of this class (in GraphQL v4)
if (!interface_exists(OperationCreator::class)) {
    return;
}

/**
 * Scaffolds a generic update operation for DataObjects.
 */
abstract class AbstractPublishOperationCreator implements OperationCreator
{
    use Configurable;
    use Injectable;

    const ACTION_PUBLISH = 'publish';
    const ACTION_UNPUBLISH = 'unpublish';

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

        $plugins = $config['plugins'] ?? [];
        $name = $config['name'] ?? null;
        if (!$name) {
            $name = $this->createOperationName($typeName);
        }
        return ModelMutation::create($model, $name)
            ->setPlugins($plugins)
            ->setType($typeName)
            ->setResolver([VersionedResolver::class, 'resolvePublishOperation'])
            ->addResolverContext('action', $this->getAction())
            ->addResolverContext('dataClass', $model->getSourceClass())
            ->addArg('id', 'ID!');
    }

    abstract protected function createOperationName(string $typeName): string;

    abstract protected function getAction(): string;
}
