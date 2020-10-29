<?php


namespace SilverStripe\GraphQL\Schema\Field;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\ModelFieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\ModelMutationPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\MutationPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * Defines a mutation created by a model
 */
class ModelMutation extends Mutation implements ModelOperation
{
    use ModelAware;

    /**
     * ModelMutation constructor.
     * @param SchemaModelInterface $model
     * @param string $mutationName
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function __construct(SchemaModelInterface $model, string $mutationName, array $config = [])
    {
        $this->setModel($model);
        parent::__construct($mutationName, $config);
    }

    /**
     * @param string $pluginName
     * @param $plugin
     * @throws SchemaBuilderException
     */
    public function validatePlugin(string $pluginName, $plugin): void
    {
        Schema::invariant(
            $plugin && (
                $plugin instanceof ModelMutationPlugin ||
                $plugin instanceof MutationPlugin ||
                $plugin instanceof FieldPlugin
            ),
            'Plugin %s not found or does not apply to model mutation "%s"',
            $pluginName,
            ModelMutationPlugin::class,
            MutationPlugin::class,
            FieldPlugin::class
        );
    }
}
