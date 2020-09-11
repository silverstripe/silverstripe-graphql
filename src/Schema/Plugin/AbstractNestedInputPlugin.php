<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Type\ModelType;

/**
 * This is an extremely complex class that is used to generate input types
 * based on a nested set of fields. It is used in the filter and sort
 * plugins to allow traversing relations.
 *
 * Fundamentally, it creates the input type, including all of its nested
 * types, and provides a utility that exports dot.separtated.fieldNames
 * that map to Dot.Separated.ObjectProperties
 */
abstract class AbstractNestedInputPlugin implements ModelQueryPlugin
{
    use Injectable;
    use Configurable;

    const SELF_REFERENTIAL = 'self';

    const SELF_REFERENTIAL_LIST = '[self]';

    /**
     * @var int
     * @config
     */
    private static $max_nesting = 1;

    /**
     * @var InputType[]
     */
    protected $allTypes = [];

    /**
     * @var array
     */
    protected $fieldMapping = [];

    /**
     * @param ModelQuery $query
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(ModelQuery $query, Schema $schema, array $config = []): void
    {
        $model = $query->getModel();
        $typeName = $model->getTypeName();

        $configFields = $config['fields'] ?? Schema::ALL;

        $modelType = $schema->getModel($typeName);
        Schema::invariant(
            $modelType,
            'Could not find model for query that uses %s. Were plugins applied before the schema was done loading?',
            $typeName
        );
        $fieldName = $this->config()->get('field_name');

        if ($configFields === Schema::ALL) {
            $configFields = $this->buildAllFieldsConfig($modelType, $schema);
        }
        Schema::assertValidConfig($configFields);
        $this->addInputTypesToSchema($modelType, $schema, $configFields);
        $rootTypeName = static::getTypeName($modelType);
        /* @var InputType $rootType */
        $rootType = $schema->getType($rootTypeName);
        $pathMapping = $this->buildPathsFromInputType($rootType, $schema);

        $fieldMapping = [];
        foreach ($pathMapping as $fieldPath => $propPath) {
            if ($fieldPath !== $propPath) {
                $fieldMapping[$fieldPath] = $propPath;
            }
        }

        $query->addArg($fieldName, $rootType->getName());


        $query->addResolverAfterware(
            $this->getResolver(),
            [
                'fieldMapping' => $fieldMapping,
                'fieldName' => $this->getFieldName(),
            ]
        );

    }

    /**
     * @param ModelType $modelType
     * @param Schema $schema
     * @param int $level
     * @return array
     * @throws SchemaBuilderException
     */
    protected function buildAllFieldsConfig(ModelType $modelType, Schema $schema, int $level = 1): array
    {
        $filters = [];
        /* @var ModelField $fieldObj */
        foreach ($modelType->getFields() as $fieldObj) {
            if (!$this->shouldAddField($fieldObj, $modelType)) {
                continue;
            }
            $fieldName = $fieldObj->getPropertyName();
            if (!$modelType->getModel()->hasField($fieldName)) {
                continue;
            }
            if ($relatedModelType = $fieldObj->getModelType()) {
                if ($level > $this->config()->get('max_nesting')) {
                    continue;
                }
                $relatedModel = $schema->getModel($relatedModelType->getName());
                Schema::invariant(
                    $relatedModel,
                    'Field %s on %s points to model %s which does not exist',
                    $fieldObj->getName(),
                    $modelType->getName(),
                    $relatedModelType->getName()
                );
                // Prevent stupid recursion in self-referential relationships, e.g. Parent
                if ($relatedModel->getName() === $modelType->getName()) {
                    $filters[$fieldObj->getPropertyName()] = $fieldObj->isList()
                        ? self::SELF_REFERENTIAL_LIST
                        : self::SELF_REFERENTIAL;
                } else {
                    $filters[$fieldObj->getPropertyName()] = $this->buildAllFieldsConfig(
                        $relatedModel,
                        $schema,
                        $level + 1
                    );
                }
            } else {
                $filters[$fieldObj->getName()] = true;
            }
        }

        return $filters;
    }

    /**
     * @param ModelType $parentModel
     * @param Schema $schema
     * @param array $fields
     * @return array
     * @throws SchemaBuilderException
     */
    protected function addInputTypesToSchema(
        ModelType $parentModel,
        Schema $schema,
        array $fields
    ): void {
        $parentInputTypeName = static::getTypeName($parentModel);
        $parentType = $schema->getType($parentInputTypeName);
        if (!$parentType) {
            $parentType = InputType::create($parentInputTypeName);
            $schema->addType($parentType);
        }
        foreach ($fields as $fieldName => $data) {
            if ($data === false) {
                continue;
            }

            /* @var ModelField $fieldObj */
            $fieldObj = $parentModel->getFieldByName($fieldName);
            $modelType = $fieldObj->getModelType();
            if (!$this->shouldAddField($fieldObj, $parentModel)) {
                continue;
            }

            if ($data === self::SELF_REFERENTIAL) {
                // Self-referential input type
                $parentType->addField($fieldName, $parentType->getName());
            } else if ($data === self::SELF_REFERENTIAL_LIST) {
                $parentType->addField($fieldName, '[' . $parentType->getName() . ']');
            } else if (!is_array($data)) {
                // Regular field, e.g. scalar
                $fieldType = $fieldObj->getNamedType();
                Schema::invariant(
                    !$modelType && in_array($fieldType, Schema::getInternalTypes()),
                    'Filter for field %s is declared as true, but the field is not a scalar type',
                    $fieldName
                );
                $parentType->addField(
                    $fieldName,
                    static::getLeafNodeType($fieldType)
                );
            } else {
                // Nested input. Recursion.
                Schema::invariant(
                    $modelType,
                    'Filter for field %s is declared as an array, but the field is not a nested object type',
                    $fieldName
                );
                $relatedModel = $schema->getModel($modelType->getName());
                $nextInputTypeName = static::getTypeName($relatedModel);
                $parentType->addField($fieldName, $nextInputTypeName);
                $this->addInputTypesToSchema($relatedModel, $schema, $data);
            }
        }
    }

    /**
     * @param InputType $inputType
     * @param Schema $schema
     * @param array $fieldOrigin
     * @param array $propOrigin
     * @param int $level
     * @return array
     * @throws SchemaBuilderException
     */
    protected function buildPathsFromInputType(
        InputType $inputType,
        Schema $schema,
        array $fieldOrigin = [],
        array $propOrigin = [],
        int $level = 0
    ): array {
        $allPaths = [];
        /* @var Field $fieldObj */
        foreach ($inputType->getFields() as $fieldObj) {
            $fieldName = $fieldObj->getName();
            $fieldPath = array_merge($fieldOrigin, [$fieldName]);
            $modelName = static::getModelName($inputType);
            /* @var ModelType $model */
            $model = $schema->getModel($modelName);
            Schema::invariant(
                $model,
                'Field "%s" on input type "%s" does not point to a valid model "%s"',
                $fieldName,
                $inputType->getName(),
                $model
            );
            $prop = static::getObjectProperty($model->getSourceClass(), $fieldName);
            $propPath = array_merge($propOrigin, [$prop]);

            $modelFieldType = $model->getFieldByName($fieldName)->getType();
            $fieldType = $fieldObj->getNamedType();
            $leafType = static::getLeafNodeType($modelFieldType);
            // This is the leaf node type. Stop here.
            if ($fieldType === $leafType) {
                $allPaths[implode('.', $fieldPath)] = implode('.', $propPath);
                continue;
            }
            // If not, it's a nested input. Keep recursing.
            $nestedType = $schema->getType($fieldType);
            $isMax = $level > $this->config()->get('max_nesting');
            if ($nestedType instanceof InputType && !$isMax) {
                $allPaths = array_merge(
                    $allPaths,
                    $this->buildPathsFromInputType($nestedType, $schema, $fieldPath, $propPath, $level + 1)
                );
            } else {
                $allPaths[implode('.', $fieldPath)] = implode('.', $propPath);
            }

        }

        return $allPaths;
    }

    /**
     * To be overloaded by subclass to get access a property on an object given an
     * input field
     *
     * @param string $class
     * @param string $fieldName
     * @return string
     */
    protected static function getObjectProperty(string $class, string $fieldName): string
    {
        return $fieldName;
    }

    /**
     * When the input reaches a leaf node, get the type, e.g. for a filter this could be
     * "String" -> { eq: String }
     *
     * @param string $internalType
     * @return string
     */
    protected static function getLeafNodeType(string $internalType): string
    {
        return $internalType;
    }

    /**
     * Public API that can be used by a resolver to flatten the input argument into
     * dot.separated.paths that can be normalised against the context provided by
     * buildPathsFromFieldMapping()
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
            if (is_array($val)) {
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
     * @param ModelField $field
     * @param ModelType $modelType
     * @return bool
     */
    protected function shouldAddField(ModelField $field, ModelType $modelType): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function getFieldMapping(): array
    {
        return $this->fieldMapping;
    }

    /**
     * @return string
     */
    abstract protected function getFieldName(): string;

    /**
     * @return array
     */
    abstract protected function getResolver(): array;

    /**
     * @param ModelType $modelType
     * @return string
     */
    abstract public static function getTypeName(ModelType $modelType): string;

    /**
     * @param InputType $inputType
     * @return string
     */
    abstract public static function getModelName(InputType $inputType): string;
}
