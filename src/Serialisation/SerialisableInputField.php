<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\GraphQL\Serialisation\CodeGen\ArrayDefinition;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\GraphQL\Serialisation\CodeGen\ConfigurableObjectInstantiator;
use SilverStripe\GraphQL\Serialisation\CodeGen\Expression;

class SerialisableInputField extends InputObjectField implements CodeGenerator
{
    /**
     * @throws Error
     */
    protected function assertSerialisable()
    {
        Utils::invariant(
            !$this->astNode,
            'Cannot serialise input type "%s" with astNode assigned',
            $this->name
        );
    }

    /**
     * @return ConfigurableObjectInstantiator|string
     * @throws NotFoundExceptionInterface
     */
    public function toCode()
    {
        /* @var TypeSerialiser $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);
        $config = [
            'name' => $this->name,
            'type' => new Expression($serialiser->exportType($this->getType())),
            'defaultValue' => $this->defaultValue,
            'description' => $this->description,
        ];

        return new ArrayDefinition($config);
    }
}