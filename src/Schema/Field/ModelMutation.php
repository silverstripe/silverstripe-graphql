<?php


namespace SilverStripe\GraphQL\Schema\Field;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;

class ModelMutation extends Query implements ModelOperation
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

}
