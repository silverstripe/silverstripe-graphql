<?php


namespace SilverStripe\GraphQL\Schema\Field;

use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;

/**
 * Used for a variety of classes that rely on SchemaModelInterface
 */
trait ModelAware
{
    /**
     * @var SchemaModelInterface
     */
    private $model;

    /**
     * @return SchemaModelInterface
     */
    public function getModel(): SchemaModelInterface
    {
        return $this->model;
    }

    /**
     * @param SchemaModelInterface $model
     * @return ModelQuery
     */
    public function setModel(SchemaModelInterface $model): self
    {
        $this->model = $model;
        return $this;
    }
}
