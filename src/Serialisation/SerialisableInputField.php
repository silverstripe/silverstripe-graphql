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

class SerialisableInputField extends InputObjectField implements TypeStoreConsumer, CodeGenerator
{
    public function loadFromTypeStore(TypeStoreInterface $typeStore)
    {
        /* @var TypeSerialiser $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);

        // If the type is defined as a string, parse it and load it from the type store
        if (!$this->getType() instanceof Type) {
            $typeCreator = $serialiser->getTypeCreator($this->getType());
            $this->type = $typeCreator($typeStore);
        }
    }

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
     * @return array
     * @throws NotFoundExceptionInterface
     * @throws Error{
     */
    public function __sleep()
    {
        $this->assertSerialisable();

        /* @var TypeSerialiser $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);

        $this->type = $serialiser->serialiseType($this->type);

        return [
            'name',
            'type',
            'defaultValue',
            'description',
        ];
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