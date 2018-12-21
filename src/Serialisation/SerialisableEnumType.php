<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Utils\Utils;
use SilverStripe\GraphQL\Serialisation\CodeGen\ArrayDefinition;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\GraphQL\Serialisation\CodeGen\ConfigurableObjectInstantiator;

class SerialisableEnumType extends EnumType implements CodeGenerator
{
    /**
     * @throws Error
     */
    protected function assertSerialisable()
    {
        Utils::invariant(
            !$this->astNode,
            'Type "%s" has ASTNodes assigned and cannot be serialised.',
            $this->name
        );

    }

    /**
     * @return ConfigurableObjectInstantiator
     * @throws Error
     */
    public function toCode()
    {
        $this->assertSerialisable();
        return new ConfigurableObjectInstantiator(
            EnumType::class,
            [
                'name' => $this->name,
                'description' => $this->description,
                'values' => new ArrayDefinition($this->config['values']),
            ]
        );
    }

    /**
     * @return array
     * @throws Error
     */
    public function __sleep()
    {
        $this->assertSerialisable();
        return [
            'name',
            'description',
            'config',
        ];
    }
}