<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Utils\Utils;
use LogicException;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use Psr\Container\NotFoundExceptionInterface;
use Closure;
use SilverStripe\GraphQL\Serialisation\CodeGen\ArrayDefinition;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\GraphQL\Serialisation\CodeGen\ConfigurableObjectInstantiator;
use SilverStripe\GraphQL\Serialisation\CodeGen\Expression;
use SilverStripe\GraphQL\Serialisation\CodeGen\FunctionDefinition;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class SerialisableObjectType extends ObjectType implements CodeGenerator
{
    /**
     * @var SerialisableFieldDefinition[]
     */
    protected $fields;

    /**
     * @return SerialisableFieldDefinition[]
     * @throws Error
     */
    public function getFields()
    {
        if ($this->fields) {
            return $this->fields;
        }
        if (is_callable($this->config['fields'])) {
            $fields = $this->config['fields']();
        } else if (is_array($this->config['fields'])) {
            $fields = $this->config['fields'];
        } else {
            throw new LogicException(sprintf(
                'Fields must be a callable or array on "%s"',
                $this->name
            ));
        }

        $serialisableFields = [];

        foreach ($fields as $fieldName => $fieldDef) {
            if (!$fieldDef instanceof FieldDefinition) {
                Deprecation::notice(
                    '4.0',
                    sprintf(
                        'Fields can no longer be defined as arrays. Please use %s instances instead.',
                        SerialisableFieldDefinition::class
                    )
                );
                if (!isset($fieldDef['name']) || !$fieldDef['name']) {
                    $fieldDef['name'] = $fieldName;
                }
                $fieldDef = SerialisableFieldDefinition::create($fieldDef);
            }
            $serialisableFields[$fieldName] = $fieldDef;
        }

        $this->fields = $serialisableFields;

        return $this->fields;
    }

    /**
     * @throws Error
     */
    protected function assertSerialisable()
    {
        Utils::invariant(
            !$this->astNode && empty($this->extensionASTNodes),
            'Type "%s" has ASTNodes assigned and cannot be serialised.',
            $this->name
        );
        Utils::invariant(
            !isset($this->config['isTypeOf']) || !$this->config['isTypeOf'] instanceof Closure,
            'Type "%s" is using a closure for the isTypeOf property and cannot be serialised.',
            $this->name
        );
        Utils::invariant(
            !$this->resolveFieldFn || !$this->resolveFieldFn instanceof Closure,
            'Type "%s" is using a closure for the resolveField property and cannot be serialised.',
            $this->name
        );
    }

    /**
     * @return ConfigurableObjectInstantiator|string
     * @throws Error
     * @throws NotFoundExceptionInterface
     */
    public function toCode()
    {
        $this->assertSerialisable();
        $fields = [];
        foreach ($this->getFields() as $fieldName => $fieldDef) {
            $fields[$fieldName] = new Expression((string) $fieldDef->toCode());
        }
        return new ConfigurableObjectInstantiator(
            ObjectType::class,
            [
                'name' => $this->name,
                'description' => $this->description,
                'fields' => new FunctionDefinition(new ArrayDefinition($fields, 4), 3),
            ]
        );
    }
}