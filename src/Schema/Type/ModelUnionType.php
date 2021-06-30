<?php


namespace SilverStripe\GraphQL\Schema\Type;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;

/**
 * Defines a union that is backed by a model definition
 */
class ModelUnionType extends UnionType
{
    use CanonicalModelAware;

    /**
     * ModelUnionType constructor.
     * @param ModelType $canonicalModel
     * @param string $name
     * @param array|null $config
     * @throws SchemaBuilderException
     */
    public function __construct(ModelType $canonicalModel, string $name, ?array $config = null)
    {
        $this->setCanonicalModel($canonicalModel);
        parent::__construct($name, $config);
    }
}
