<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use SilverStripe\GraphQL\Serialisation\CodeGen\ArrayDefinition;
use SilverStripe\GraphQL\Serialisation\CodeGen\CodeGenerator;
use SilverStripe\GraphQL\Serialisation\CodeGen\ConfigurableObjectInstantiator;
use SilverStripe\GraphQL\Serialisation\CodeGen\Expression;
use SilverStripe\GraphQL\Serialisation\CodeGen\FunctionDefinition;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class SerialisableInputType extends InputObjectType implements TypeStoreConsumer, CodeGenerator
{

    protected $fields;

    /**
     * @return InputObjectField[]
     */
    public function getFields()
    {
        if (null === $this->fields) {
            $this->fields = [];
            $fields = isset($this->config['fields']) ? $this->config['fields'] : [];
            $fields = is_callable($fields) ? call_user_func($fields) : $fields;

            if (!is_array($fields)) {
                throw new InvariantViolation(
                    "{$this->name} fields must be an array or a callable which returns such an array."
                );
            }

            foreach ($fields as $name => $field) {
                if ($field instanceof Type) {
                    $field = ['type' => $field];
                }
                $field = new SerialisableInputField($field + ['name' => $name]);
                $this->fields[$field->name] = $field;
            }
        }

        return $this->fields;
    }

    public function loadFromTypeStore(TypeStoreInterface $typeStore)
    {
        foreach ($this->getFields() as $fieldName => $field) {
            $field->loadFromTypeStore($typeStore);
        }
    }

    /**
     * @throws Error
     */
    protected function assertSerialisable()
    {
        Utils::invariant(
            !$this->astNode,
            'Type "%s" has ASTNode assigned and cannot be serialised.',
            $this->name
        );
    }

    public function __sleep()
    {
        $this->assertSerialisable();

        return [
            'name',
            'description',
            'fields',
        ];
    }

    public function toCode()
    {
        $this->assertSerialisable();
        $fields = [];
        foreach ($this->getFields() as $fieldName => $fieldDef) {
            $fields[$fieldName] = new Expression((string) $fieldDef->toCode());
        }
        return new ConfigurableObjectInstantiator(
            InputObjectType::class,
            [
                'name' => $this->name,
                'description' => $this->description,
                'fields' => new FunctionDefinition(new ArrayDefinition($fields)),
            ]
        );
    }

//    public function toCode()
//    {
//        $this->assertSerialisable();
//        $fields = ArrayList::create();
//        foreach ($this->getFields() as $fieldName => $fieldDef) {
//            $fields->push(ArrayData::create([
//                'Name' => $fieldName,
//                'Expression' => $fieldDef->toCode(),
//            ]));
//        }
//
//        return ArrayData::create([
//            'ClassName' => InputObjectType::class,
//            'Name' => $this->name,
//            'Description' => $this->description,
//            'Fields' => $fields
//        ]);
//    }

//    public function render()
//    {
//        return $this->toCode()->renderWith('GraphQLObjectType');
//    }
}