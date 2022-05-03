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
     * @throws SchemaBuilderException
     */
    public function __construct(ModelType $canonicalModel, string $name, ?array $config = null)
    {
        $this->setCanonicalModel($canonicalModel);
        parent::__construct($name, $config);
    }
}
