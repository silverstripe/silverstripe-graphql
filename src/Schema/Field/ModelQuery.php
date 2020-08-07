<?php


namespace SilverStripe\GraphQL\Schema\Field;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\QueryPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Schema;

class ModelQuery extends Query implements ModelOperation
{
    use ModelAware;

    /**
     * ModelQuery constructor.
     * @param SchemaModelInterface $model
     * @param string $queryName
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function __construct(SchemaModelInterface $model, string $queryName, array $config = [])
    {
        $this->setModel($model);
        parent::__construct($queryName, $config);
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
                $plugin instanceof ModelQueryPlugin ||
                $plugin instanceof QueryPlugin ||
                $plugin instanceof FieldPlugin
            ),
            'Plugin %s not found or not an instance of %s',
            $pluginName,
            ModelQueryPlugin::class
        );

    }

}
