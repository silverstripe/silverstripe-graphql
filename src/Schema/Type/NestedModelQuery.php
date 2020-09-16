<?php


namespace SilverStripe\GraphQL\Schema\Type;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;

class NestedModelQuery extends ModelField
{
    /**
     * @var SchemaModelInterface
     */
    private $parentModel;

    /**
     * NestedModelQuery constructor.
     * @param SchemaModelInterface $parentModel
     * @param SchemaModelInterface $childModel
     * @param string $queryName
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function __construct(
        SchemaModelInterface $parentModel,
        SchemaModelInterface $childModel,
        string $queryName,
        array $config = []
    ) {
        $this->parentModel = $parentModel;
        parent::__construct($queryName, $childModel, $queryName, $config);
    }
}
