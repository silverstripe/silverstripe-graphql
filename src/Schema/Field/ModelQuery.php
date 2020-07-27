<?php


namespace SilverStripe\GraphQL\Schema\Field;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;

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

}
