<?php


namespace SilverStripe\GraphQL\Schema\Services;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Schema\Type\TypeReference;
use SilverStripe\ORM\ArrayLib;

/**
 * An agnostic service that builds an input type based on a given field, with nesting.
 * Composable typeName functionality and handling of leaf nodes (e.g. turning the
 * leaf node into a SortDirection enum.
 */
class NestedInputBuilder
{
    use Injectable;
    use Configurable;

    const SELF_REFERENTIAL = '--self--';

    /**
     * @var int
     * @config
     */
    private static $max_nesting = 3;

    /**
     * @var string
     * @config
     */
    private static $prefix = '';

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var Field
     */
    private $root;

    /**
     * @var string|array
     */
    private $fields;

    /**
     * @var callable
     */
    private $fieldFilter;

    /**
     * @var callable
     */
    private $typeNameHandler;

    /**
     * @var callable
     */
    private $leafNodeHandler;

    /**
     * @var InputType
     */
    private $rootType;

    /**
     * NestedInputBuilder constructor.
     * @param Field $root
     * @param string $fields
     * @throws SchemaBuilderException
     */
    public function __construct(Field $root, Schema $schema, $fields = Schema::ALL)
    {
        $this->schema = $schema;
        $this->root = $root;

        Schema::invariant(
            is_array($fields) || $fields === Schema::ALL,
            'Fields must be an array or %s',
            Schema::ALL
        );

        $this->fields = $fields;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function populateSchema()
    {
        $typeName = TypeReference::create($this->root->getType())->getNamedType();
        $type = $this->schema->getTypeOrModel($typeName);
        Schema::invariant(
            $type,
            'Could not find type for query that uses %s. Were plugins applied before the schema was done loading?',
            $typeName
        );

        $prefix = static::config()->get('prefix');

        if ($this->fields === Schema::ALL) {
            $this->fields = $this->buildAllFieldsConfig($type);
        }
        $this->addInputTypesToSchema($type, $this->fields, null, null, $prefix);
        $rootTypeName = $prefix . $this->getTypeName($type);
        $rootType = $this->schema->getType($rootTypeName);
        $this->rootType = $rootType;
    }

    /**
     * @param Type $type
     * @param int $level
     * @return array
     * @throws SchemaBuilderException
     */
    protected function buildAllFieldsConfig(Type $type, int $level = 0): array
    {
        $existing = $this->fetch($type->getName());
        if ($existing) {
            return $existing;
        }
        $level++;
        $map = [];
        foreach ($type->getFields() as $fieldObj) {
            if (!$this->shouldAddField($type, $fieldObj)) {
                continue;
            }
            $namedType = $fieldObj->getNamedType();
            $nestedType = $this->schema->getTypeOrModel($namedType);
            if ($nestedType) {
                if ($level > $this->config()->get('max_nesting')) {
                    continue;
                }
                // Prevent stupid recursion in self-referential relationships, e.g. Parent
                if ($namedType === $type->getName()) {
                    $map[$fieldObj->getName()] = self::SELF_REFERENTIAL;
                } else {
                    $map[$fieldObj->getName()] = $this->buildAllFieldsConfig($nestedType, $level);
                }
            } else {
                $map[$fieldObj->getName()] = true;
            }
        }
        $this->persist($type->getName(), $map);

        return $map;
    }

    /**
     * @param Type $type
     * @param array $fields
     * @param InputType|null $parentType
     * @param string|null $parentField
     * @param string $prefix
     * @throws SchemaBuilderException
     */
    protected function addInputTypesToSchema(
        Type $type,
        array $fields,
        ?InputType $parentType = null,
        ?string $parentField = null,
        string $prefix = ''
    ): void {
        $inputTypeName = $prefix . $this->getTypeName($type);
        $inputType = $this->schema->getType($inputTypeName);
        if (!$inputType) {
            $inputType = InputType::create($inputTypeName);
        }
        foreach ($fields as $fieldName => $data) {
            if ($data === false) {
                continue;
            }
            // If we've already seen this input, it can only be added to, not mutated.
            if ($inputType->getFieldByName($fieldName)) {
                continue;
            }

            $fieldObj = $type->getFieldByName($fieldName);
            if (!$fieldObj && $type instanceof ModelType) {
                $fieldObj = $type->getModel()->getField($fieldName);
            }
            Schema::invariant(
                $fieldObj,
                'Could not find field "%s" on type "%s"',
                $fieldName,
                $type->getName()
            );

            if (!$this->shouldAddField($type, $fieldObj)) {
                continue;
            }

            $fieldType = $fieldObj->getNamedType();
            $nestedType = $this->schema->getTypeOrModel($fieldType);

            if ($data === self::SELF_REFERENTIAL) {
                $inputType->addField($fieldName, $inputType->getName());
            } elseif (!is_array($data)) {
                // Regular field, e.g. scalar
                if (!$nestedType && Schema::isInternalType($fieldType)) {
                    $inputType->addField(
                        $fieldName,
                        $this->getLeafNodeType($fieldType)
                    );
                }
            }
            if ($inputType->exists()) {
                $this->schema->addType($inputType);
                if ($parentType && $parentField) {
                    $parentType->addField($parentField, $inputType->getName());
                }
            }
            if (is_array($data)) {
                // Nested input. Recursion.
                Schema::invariant(
                    $nestedType,
                    'Filter for field %s is declared as an array, but the field is not a nested object type',
                    $fieldName
                );
                $this->addInputTypesToSchema($nestedType, $data, $inputType, $fieldName, $prefix);
            }
        }
    }

    /**
     * @param callable $fieldFilter
     * @return NestedInputBuilder
     */
    public function setFieldFilter(callable $fieldFilter): NestedInputBuilder
    {
        $this->fieldFilter = $fieldFilter;
        return $this;
    }

    /**
     * @param callable $typeNameHandler
     * @return NestedInputBuilder
     */
    public function setTypeNameHandler(callable $typeNameHandler): NestedInputBuilder
    {
        $this->typeNameHandler = $typeNameHandler;
        return $this;
    }

    /**
     * @param callable $leafNodeHandler
     * @return NestedInputBuilder
     */
    public function setLeafNodeHandler(callable $leafNodeHandler): NestedInputBuilder
    {
        $this->leafNodeHandler = $leafNodeHandler;
        return $this;
    }

    /**
     * @return InputType|null
     */
    public function getRootType(): ?InputType
    {
        return $this->rootType;
    }

    /**
     * Public API that can be used by a resolver to flatten the input argument into
     * dot.separated.paths that can be normalised
     *
     * @param array $argFilters
     * @param array $origin
     * @return array
     */
    public static function buildPathsFromArgs(array $argFilters, array $origin = []): array
    {
        $allPaths = [];
        foreach ($argFilters as $fieldName => $val) {
            $path = array_merge($origin, [$fieldName]);
            if (ArrayLib::is_associative($val)) {
                $allPaths = array_merge($allPaths, static::buildPathsFromArgs($val, $path));
            } else {
                $allPaths[implode('.', $path)] = $val;
            }
        }

        return $allPaths;
    }


    /**
     * Allows certain fields to be excluded
     *
     * @param Type $type
     * @param Field $field
     * @return bool
     */
    private function shouldAddField(Type $type, Field $field): bool
    {
        if ($this->fieldFilter) {
            return call_user_func_array($this->fieldFilter, [$type, $field]);
        }

        return true;
    }

    /**
     * @param Type $type
     * @return string
     */
    private function getTypeName(Type $type): string
    {
        if ($this->typeNameHandler) {
            return call_user_func_array($this->typeNameHandler, [$type]);
        }

        return $type->getName() . 'InputType';
    }

    /**
     * @param string $fieldName
     * @return string
     */
    private function getLeafNodeType(string $fieldName): string
    {
        if ($this->leafNodeHandler) {
            return call_user_func_array($this->leafNodeHandler, [$fieldName]);
        }

        return $fieldName;
    }

    /**
     * @param string $key
     * @param $value
     * @throws SchemaBuilderException
     */
    private function persist(string $key, $value): void
    {
        $this->schema->getSchemaContext()->set([__CLASS__, $key], $value);
    }

    /**
     * @param string $key
     * @return array|mixed
     * @throws SchemaBuilderException
     */
    private function fetch(string $key)
    {
        return $this->schema->getSchemaContext()->get([__CLASS__, $key]);
    }
}
