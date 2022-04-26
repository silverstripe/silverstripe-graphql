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
    use CanonicalModelAware;

    /**
     * @throws SchemaBuilderException
     */
    public function __construct(ModelType $modelType, string $name, ?array $config = null)
    {
        $this->setCanonicalModel($modelType);
        parent::__construct($name, $config);
    }
}
