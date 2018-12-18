<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;

class SerialisableInputType extends InputObjectType implements TypeStoreConsumer
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
}