<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Type\ModelType;

abstract class AbstractNestedInputPlugin implements ModelQueryPlugin
{
    use Injectable;
    use Configurable;

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
            $configFields = $this->buildAllFieldsConfig($modelType);
        }

        Schema::assertValidConfig($configFields);
        $fields = $this->buildInputTypeFields($modelType, $configFields);
        $allTypes = $this->extractTypes(
            static::getTypeName($modelType),
            $fields
        );

        $fieldGraph = $this->getRelationalModelGraph($modelType, $fields);
        $pathMapping = $this->buildPathsFromFieldMapping($fieldGraph);
        $fieldMapping = [];
        foreach ($pathMapping as $fieldPath => $propPath) {
            if ($fieldPath !== $propPath) {
                $fieldMapping[$fieldPath] = $propPath;
            }
        }

        $query->addArg($fieldName, static::getTypeName($modelType));

        foreach ($allTypes as $inputType) {
            $schema->addType($inputType);
        }

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
     * @return array
     * @throws SchemaBuilderException
     */
    protected function buildAllFieldsConfig(ModelType $modelType): array
    {
        $filters = [];
        /* @var ModelField $fieldObj */
        foreach ($modelType->getFields() as $fieldObj) {
            $fieldName = $fieldObj->getPropertyName();
            if (!$modelType->getModel()->hasField($fieldName)) {
                continue;
            }
            if ($relatedModel = $fieldObj->getModelType()) {
                $filters[$fieldObj->getPropertyName()] = $this->buildAllFieldsConfig($relatedModel);
            } else {
                $filters[$fieldObj->getName()] = true;
            }
        }

        return $filters;
    }

    /**
     * @param ModelType|null $modelType
     * @param array $fields
     * @return array
     * @throws SchemaBuilderException
     */
    protected function buildInputTypeFields(ModelType $modelType, array $fields): array
    {
        $filters = [];
        foreach ($fields as $fieldName => $data) {
            if ($data === false) {
                continue;
            }
            /* @var ModelField $fieldObj */
            $fieldObj = $modelType->getFieldByName($fieldName);
            $relatedModel = $fieldObj->getModelType();
            if (!$this->shouldAddField($fieldObj, $modelType)) {
                continue;
            }
            if (is_array($data)) {
                Schema::invariant(
                    $relatedModel,
                    'Filter for field %s is declared as an array, but the field is not a nested object type',
                    $fieldName
                );
                $filters[$fieldName] = [
                    'children' => $this->buildInputTypeFields($relatedModel, $data),
                    'type' => static::getTypeName($relatedModel),
                ];
            } else if ($data === true) {
                $fieldType = $fieldObj->getNamedType();
                Schema::invariant(
                    !$relatedModel && in_array($fieldType, Schema::getInternalTypes()),
                    'Filter for field %s is declared as true, but the field is not a scalar type',
                    $fieldName
                );
                $filters[$fieldName] = [
                    'type' => static::getLeafNodeType($fieldType),
                    'children' => null
                ];
            }
        }


        return $filters;
    }

    /**
     * @param string $typeName
     * @param array $filters
     * @param array $allTypes
     * @return array
     * @throws SchemaBuilderException
     */
    protected function extractTypes(
        string $typeName,
        array $filters,
        array $allTypes = []
    ): array {
        $fields = [];
        foreach ($filters as $fieldName => $data) {
            $children = $data['children'];
            $childTypeName = $data['type'];
            if ($children) {
                $cumulativeTypes = $this->extractTypes(
                    $childTypeName,
                    $children,
                    $allTypes
                );
                $allTypes = array_merge($allTypes, $cumulativeTypes);
                $fields[$fieldName] = $childTypeName;
            } else {
                $fields[$fieldName] = $childTypeName;
            }
        }
        $type = InputType::create($typeName)
            ->setFields($fields);
        $allTypes[$typeName] = $type;

        return $allTypes;
    }

    /**
     * @param ModelType|null $modelType
     * @param array $fields
     * @return array
     * @throws SchemaBuilderException
     */
    protected function getRelationalModelGraph(ModelType $modelType, array $fields): array
    {
        $mapping = [];
        /* @var DataObjectModel $model */
        foreach ($fields as $fieldName => $data) {
            $children = $data['children'];
            /* @var ModelField $fieldObj */
            $fieldObj = $modelType->getFieldByName($fieldName);
            Schema::invariant(
                $fieldObj,
                'Could not map field %s',
                $fieldName
            );
            $isNested = is_array($children);
            $class = $modelType->getModel()->getSourceClass();
            if ($isNested) {
                $relatedModel = $fieldObj->getModelType();
                Schema::invariant(
                    $relatedModel,
                    'Cannot find related model type for field %s',
                    $fieldName
                );
                $mapping[$fieldName] = [
                    'class' => $class,
                    'children' => $this->getRelationalModelGraph($relatedModel, $children)
                ];
            } else {
                $mapping[$fieldName] = [
                    'class' => $class,
                    'children' => null
                ];
            }
        }

        return $mapping;
    }


    /**
     * @param array $mapping
     * @param array $fieldOrigin
     * @param array $propOrigin
     * @return array
     */
    protected function buildPathsFromFieldMapping(
        array $mapping,
        array $fieldOrigin = [],
        array $propOrigin = []
    ): array {
        $allPaths = [];
        foreach ($mapping as $fieldName => $config) {
            $fieldPath = array_merge($fieldOrigin, [$fieldName]);
            $prop = static::getObjectProperty($config['class'], $fieldName);
            $propPath = array_merge($propOrigin, [$prop]);
            $children = $config['children'] ?? null;
            if (is_array($children)) {
                $allPaths = array_merge(
                    $allPaths,
                    $this->buildPathsFromFieldMapping($children, $fieldPath, $propPath)
                );
            } else {
                $allPaths[implode('.', $fieldPath)] = implode('.', $propPath);
            }
        }

        return $allPaths;
    }

    /**
     * @param string $class
     * @param string $fieldName
     * @return string
     */
    protected static function getObjectProperty(string $class, string $fieldName): string
    {
        return $fieldName;
    }

    /**
     * @param string $internalType
     * @return string
     */
    protected static function getLeafNodeType(string $internalType): string
    {
        return $internalType;
    }

    /**
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
}
