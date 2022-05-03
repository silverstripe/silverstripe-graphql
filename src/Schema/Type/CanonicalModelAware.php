<?php


namespace SilverStripe\GraphQL\Schema\Type;

trait CanonicalModelAware
{
    private ?ModelType $canonicalModel;

    public function getCanonicalModel(): ?ModelType
    {
        return $this->canonicalModel;
    }

    public function setCanonicalModel(ModelType $modelType): self
    {
        $this->canonicalModel = $modelType;

        return $this;
    }
}
