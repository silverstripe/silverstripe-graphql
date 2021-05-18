<?php


namespace SilverStripe\GraphQL\Schema\Type;

trait CanonicalModelAware
{
    /**
     * @var ModelType
     */
    private $canonicalModel;

    /**
     * @return ModelType
     */
    public function getCanonicalModel(): ModelType
    {
        return $this->canonicalModel;
    }

    /**
     * @param ModelType $modelType
     * @return $this
     */
    public function setCanonicalModel(ModelType  $modelType): self
    {
        $this->canonicalModel = $modelType;

        return $this;
    }
}
