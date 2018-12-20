<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use Serializable;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;

abstract class CRUDResolverFactory implements Serializable, CodeGenerator
{
    use Injectable;
    use Extensible;
    use DataObjectTypeTrait;

    /**
     * CRUDResolverFactory constructor.
     * @param $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->setDataObjectClass($dataObjectClass);
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize([
            'dataObjectClass' => $this->dataObjectClass
        ]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->setDataObjectClass($data['dataObjectClass']);
    }

    public function toCode()
    {
        return sprintf(
            'new %s(%s)',
            static::class,
            var_export($this->dataObjectClass, true)
        );
    }
}