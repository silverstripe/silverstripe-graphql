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

class SerialisableObjectType extends ObjectType implements TypeStoreConsumer
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
     * @param TypeStoreInterface $typeStore
     * @throws Error
     * @throws NotFoundExceptionInterface
     */
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
     * @throws Error
     */
    public function __sleep()
    {
        $this->assertSerialisable();
        $this->fields = $this->getFields();
        return [
            'name',
            'description',
            'fields',
        ];
    }

}