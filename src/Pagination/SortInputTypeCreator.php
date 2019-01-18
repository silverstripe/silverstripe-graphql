<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Components\Enum;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\Input;
use SilverStripe\GraphQL\Schema\Components\TypeReference;
use SilverStripe\GraphQL\TypeCreator;
use Psr\Container\NotFoundExceptionInterface;
/**
 * Type creator for an enum value for a list of possible sortable fields
 *
 * @see SortDirectionTypeCreator
 */
class SortInputTypeCreator extends TypeCreator
{
    /**
     * @var Input
     */
    protected $type;

    /**
     * @var string
     */
    protected $inputName;

    /**
     * @var array Keyed by field argument name, values as DataObject column names.
     * Does not support in-memory sorting for composite values (getters).
     */
    protected $sortableFields = [];

    protected $inputObject = true;

    /**
     * @var Enum
     */
    protected $fieldType;

    /**
     * Build a sort input creator with a given name prefix.
     * @param string $name Prefix for this input type name.
     */
    public function __construct($name)
    {
        parent::__construct();
        $this->inputName = $name;
    }

    /**
     * Specify the list of sortable fields
     *
     * @param array $sortableFields
     * @return $this
     */
    public function setSortableFields($sortableFields)
    {
        $this->sortableFields = $sortableFields;
        return $this;
    }

    public function toType()
    {
        if (!$this->type) {
            $this->type = parent::toType();
        }
        return $this->type;
    }
    public function getName()
    {
        return ucfirst($this->inputName) .'SortInputType';
    }

    public function getFieldTypeName()
    {
        return ucfirst($this->inputName) . 'SortFieldType';
    }

    public function attributes()
    {
        return [
            'name' => $this->getName(),
            'description' => 'Define the sorting',
            'fields' => $this->fields()
        ];
    }

    public function getFieldType()
    {
        if ($this->fieldType) {
            return $this->fieldType;
        }

        $values = [];
        foreach ($this->sortableFields as $fieldAlias => $fieldName) {
            $values[$fieldAlias] = [
                'value' => $fieldAlias
            ];
        }

        $this->fieldType = new Enum(
            $this->getFieldTypeName(),
            'Field name to sort by.',
            $values
        );

        return $this->fieldType;
    }

    /**
     * @return Enum
     * @throws NotFoundExceptionInterface
     */
    public function getSortDirectionType()
    {
        return Injector::inst()->get(SortDirectionTypeCreator::class)->toType();
    }

    /**
     * @return array
     * @throws NotFoundExceptionInterface
     */
    public function fields()
    {
        return [
            Field::create(
                'field',
                TypeReference::create($this->getFieldTypeName())
            )->setDescription('Sort field name.'),
            Field::create(
                'direction',
                TypeReference::create($this->getSortDirectionType()->getName())
            )->setDescription('Sort direction (ASC / DESC)')
        ];
    }
}
