<?php


namespace SilverStripe\GraphQL\Schema\Type;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelAware;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;

/**
 * Defines an interface that is backed by a model
 */
class ModelInterfaceType extends InterfaceType
{
    use ModelAware;

    /**
     * ModelInterfaceType constructor.
     * @param SchemaModelInterface $model
     * @param string $name
     * @param array|null $config
     * @throws SchemaBuilderException
     */
    public function __construct(SchemaModelInterface $model, string $name, ?array $config = null)
    {
        $this->setModel($model);
        parent::__construct($name, $config);
    }
}
