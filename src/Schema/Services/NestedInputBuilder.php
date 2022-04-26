<?php


namespace SilverStripe\GraphQL\Schema\Services;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\ModelUnionType;
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
     * @config
     */
    private static string $prefix = '';

    private Schema $schema;

    private Field $root;

    /**
     * @var string|array
     */
    private $fields;

    /**
     * @var callable|null
     */
    private $fieldFilter;

    /**
     * @var callable|null
     */
    private $typeNameHandler;

    /**
     * @var callable|null
     */
    private $leafNodeHandler;

    private InputType $rootType;

    private array $resolveConfig;

    /**
     * @param string|array $fields
     * @throws SchemaBuilderException
     */
    public function __construct(Field $root, Schema $schema, $fields = Schema::ALL, array $resolveConfig = [])
    {
        $this->schema = $schema;
        $this->root = $root;

        Schema::invariant(
            is_array($fields) || $fields === Schema::ALL,
            'Fields must be an array or %s',
            Schema::ALL
        );

        $this->fields = $fields;
        $this->setResolveConfig($resolveConfig);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function populateSchema(): void
    {
        $typeName = TypeReference::create($this->root->getType())->getNamedType();
        $type = $this->schema->getCanonicalType($typeName);
        Schema::invariant(
            $type,
            'Could not find type for query that uses %s. Were plugins applied before the schema was done loading?',
            $typeName
        );

        $prefix = static::config()->get('prefix');

        if ($this->fields === Schema::ALL) {
            $this->fields = $this->buildAllFieldsConfig($type);
        } elseif (isset($this->fields[Schema::ALL]) && $this->fields[Schema::ALL]) {
            unset($this->fields[Schema::ALL]);
            $this->fields = array_merge($this->fields, $this->buildAllFieldsConfig($type));
        }
        $this->addInputTypesToSchema($type, $this->fields, null, null, $prefix);
        $rootTypeName = $prefix . $this->getTypeName($type);
        $rootType = $this->schema->getType($rootTypeName);
        $this->rootType = $rootType;
    }

    /**
     * @throws SchemaBuilderException
     */
    protected function buildAllFieldsConfig(Type $type): array
    {
        $existing = $this->fetch($type->getName());
        if ($existing) {
            return $existing;
        }
        $map = [];
        foreach ($type->getFields() as $fieldObj) {
            if (!$this->shouldAddField($type, $fieldObj)) {
                continue;
            }
            $namedType = $fieldObj->getNamedType();
            $nestedType = $this->schema->getCanonicalType($namedType);
            if ($nestedType) {
                $seen = $this->schema->getState()->get([
                  static::class,
                  'seenConnections',
                  $type->getName(),
                  $fieldObj->getName()
                ]);
                // Prevent stupid recursion in self-referential relationships, e.g. Parent
                if ($namedType === $type->getName()) {
                    $map[$fieldObj->getName()] = self::SELF_REFERENTIAL;
                } elseif ($seen) {
                    continue;
                } else {
                    $this->schema->getState()->set([
                      static::class,
                      'seenConnections',
                      $type->getName(),
                      $fieldObj->getName()
                    ], true);
                    $map[$fieldObj->getName()] = $this->buildAllFieldsConfig($nestedType);
                }
            } else {
                $map[$fieldObj->getName()] = true;
            }
        }
        $this->persist($type->getName(), $map);
        return $map;
    }

    /**
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
            if ($fieldName === Schema::ALL) {
                $this->buildAllFieldsConfig($type);
            }
            if ($data === false) {
                continue;
            }
            // If we've already seen this input, it can only be added to, not mutated.
            if ($inputType->getFieldByName($fieldName)) {
                if ($inputType->exists() && $parentType && $parentField) {
                    $this->schema->addType($inputType);
                    $parentType->addField($parentField, $inputType->getName());
                }
                continue;
            }

            $fieldObj = $type->getFieldByName($fieldName);
            if (!$fieldObj && $type instanceof ModelType) {
                $fieldObj = $type->getModel()->getField($fieldName);
            }

            $customResolver = $this->getResolver($fieldName);
            $customType = $this->getResolveType($fieldName);

            Schema::invariant(
                $fieldObj || ($customResolver && $customType),
                'Could not find field "%s" on type "%s". If it is a custom filter field, you will need to provide a
                resolver function in the "resolver" config for that field along with an explicit type.',
                $fieldName,
                $type->getName()
            );

            if (!$fieldObj) {
                $fieldObj = Field::create($fieldName, [
                    'type' => $customType,
                    'resolver' => $customResolver,
                ]);
            }

            if (!$this->shouldAddField($type, $fieldObj)) {
                continue;
            }

            $fieldType = $fieldObj->getNamedType();
            $nestedType = $this->schema->getCanonicalType($fieldType);

            $isScalar = (bool) Schema::isInternalType($fieldType) || $this->schema->getEnum($fieldType);

            if ($data === self::SELF_REFERENTIAL) {
                $inputType->addField($fieldName, $inputType->getName());
            } elseif (!is_array($data) && !$nestedType && $isScalar) {
                // Regular field, e.g. scalar
                $inputType->addField(
                    $fieldName,
                    $this->getLeafNodeType($fieldType)
                );
            }
            // Make sure the input type got at least one field
            if ($inputType->exists()) {
                // Optimistically add the type to the schema
                $this->schema->addType($inputType);
                // If we're in recursion, apply the nested input type to the parent
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

    public function setFieldFilter(callable $fieldFilter): NestedInputBuilder
    {
        $this->fieldFilter = $fieldFilter;
        return $this;
    }

    public function setTypeNameHandler(callable $typeNameHandler): NestedInputBuilder
    {
        $this->typeNameHandler = $typeNameHandler;
        return $this;
    }

    public function setLeafNodeHandler(callable $leafNodeHandler): NestedInputBuilder
    {
        $this->leafNodeHandler = $leafNodeHandler;
        return $this;
    }

    public function getRootType(): ?InputType
    {
        return $this->rootType;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function setResolveConfig(array $config): self
    {
        foreach ($config as $fieldName => $data) {
            Schema::invariant(
                is_string($fieldName) && isset($data['resolver']) && isset($data['type']),
                '"resolve" setting for nested input must be a map of field name keys to an array that contains
                a "resolver" field and "type" key'
            );
        }

        $this->resolveConfig = $config;

        return $this;
    }

    public function getResolveConfig(): array
    {
        return $this->resolveConfig;
    }

    /**
     * @return string|array|null
     */
    public function getResolver(string $name)
    {
        return $this->resolveConfig[$name]['resolver'] ?? null;
    }

    public function getResolveType(string $name): ?string
    {
        return $this->resolveConfig[$name]['type'] ?? null;
    }

    public function getResolvers(): array
    {
        $resolvers = [];
        foreach ($this->resolveConfig as $fieldName => $config) {
            $resolvers[$fieldName] = $config['resolver'];
        }

        return $resolvers;
    }

    /**
     * Public API that can be used by a resolver to flatten the input argument into
     * dot.separated.paths that can be normalised
     *
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
     */
    private function shouldAddField(Type $type, Field $field): bool
    {
        if ($this->fieldFilter) {
            return call_user_func_array($this->fieldFilter, [$type, $field]);
        }

        return true;
    }

    private function getTypeName(Type $type): string
    {
        if ($this->typeNameHandler) {
            return call_user_func_array($this->typeNameHandler, [$type]);
        }

        return $type->getName() . 'InputType';
    }

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
        $this->schema->getState()->set([__CLASS__, $key], $value);
    }

    /**
     * @param string $key
     * @return array|mixed
     * @throws SchemaBuilderException
     */
    private function fetch(string $key)
    {
        return $this->schema->getState()->get([__CLASS__, $key]);
    }
}
