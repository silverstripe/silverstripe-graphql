<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Config\ModelConfiguration;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\BaseFieldsProvider;
use SilverStripe\GraphQL\Schema\Interfaces\DefaultFieldsProvider;
use SilverStripe\GraphQL\Schema\Interfaces\ModelBlacklist;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\OperationProvider;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\UnsavedRelationList;

/**
 * Defines the model that generates types, queries, and mutations based on DataObjects
 */
class DataObjectModel implements
    SchemaModelInterface,
    OperationProvider,
    DefaultFieldsProvider,
    BaseFieldsProvider,
    ModelBlacklist
{
    use Injectable;
    use Configurable;

    /**
     * @var SchemaConfig
     */
    private $schemaConfig;

    /**
     * @var array
     */
    private static $dependencies = [
        'FieldAccessor' => '%$' . FieldAccessor::class,
    ];

    /**
     * @var array
     * @config
     */
    private $operations = [];

    /**
     * @var DataObject
     */
    private $dataObject;

    /**
     * @var FieldAccessor
     */
    private $fieldAccessor;

    /**
     * @return string
     */
    public static function getIdentifier(): string
    {
        return 'DataObject';
    }

    /**
     * DataObjectModel constructor.
     * @param string $class
     * @param SchemaConfig $config
     * @throws SchemaBuilderException
     * @throws NotFoundExceptionInterface
     */
    public function __construct(string $class, SchemaConfig $config)
    {
        Schema::invariant(
            is_subclass_of($class, DataObject::class),
            '%s only accepts %s subclasses',
            static::class,
            DataObject::class
        );
        $this->dataObject = Injector::inst()->get($class);
        $this->schemaConfig = $config;
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    public function hasField(string $fieldName): bool
    {
        return $this->getFieldAccessor()->hasField($this->dataObject, $fieldName);
    }

    /**
     * @param string $fieldName
     * @param array $config
     * @return ModelField|null
     * @throws SchemaBuilderException
     */
    public function getField(string $fieldName, array $config = []): ?ModelField
    {
        $result = $this->getFieldAccessor()->accessField($this->dataObject, $fieldName);
        if (!$result) {
            return null;
        }

        $hasExplicitType = isset($config['type']);

        if ($result instanceof DBField) {
            $fieldConfig = array_merge([
                'type' => $result->config()->get('graphql_type'),
            ], $config);

            $modelField = ModelField::create($fieldName, $fieldConfig, $this);
            if (!$hasExplicitType) {
                $this->applyMetadataClass($modelField, get_class($result));
            }
            return $modelField;
        }

        $class = $this->getModelClass($result);
        if (!$class) {
            if ($this->isList($result)) {
                $modelField = ModelField::create($fieldName, $config, $this);
                if (!$hasExplicitType) {
                    $this->applyMetadataClass($modelField, $class);
                }
                return $modelField;
            }
            return null;
        }
        $type = (new static($class, $this->getSchemaConfig()))->getTypeName();
        if ($this->isList($result)) {
            $queryConfig = array_merge([
                'type' => sprintf('[%s!]!', $type),
            ], $config);
            $query = ModelQuery::create($this, $fieldName, $queryConfig);
            $query->setDefaultPlugins($this->getModelConfiguration()->getNestedQueryPlugins());

            return $query;
        }
        return ModelField::create($fieldName, $type, $this);
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getDefaultFields(): array
    {
        $fields = $this->getModelConfiguration()->getDefaultFields();
        $map = [];
        foreach ($fields as $name => $type) {
            if ($type === false) {
                continue;
            }
            $formatted = $this->getFieldAccessor()->formatField($name);
            $map[$formatted] = $type;
        }

        return $map;
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getBaseFields(): array
    {
        $fields = $this->getModelConfiguration()->getBaseFields();
        $map = [];
        foreach ($fields as $name => $type) {
            Schema::invariant(
                $type,
                'Default field %s cannot be falsy on %s',
                $name,
                $this->getSourceClass()
            );
            $formatted = $this->getFieldAccessor()->formatField($name);
            $map[$formatted] = $type;
        }

        return $map;
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getBlacklistedFields(): array
    {
        $class = $this->getSourceClass();
        $config = DataObject::singleton($class)->config()->get('graphql_blacklisted_fields') ?? [];
        $blackList = [];
        Schema::assertValidConfig($config);
        foreach ($config as $fieldName => $bool) {
            if ($bool === true && is_string($fieldName)) {
                $blackList[] = $fieldName;
            }
        }
        return array_map(function (string $field) {
            return $this->getFieldAccessor()->formatField($field);
        }, $blackList ?? []);
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getAllFields(): array
    {
        $blackList = $this->getBlacklistedFields();
        $allFields = $this->getFieldAccessor()->getAllFields($this->dataObject);

        return array_diff($allFields ?? [], $blackList);
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getUninheritedFields(): array
    {
        $blackList = $this->getBlacklistedFields();
        $allFields = $this->getFieldAccessor()->getAllFields($this->dataObject, true, false);

        return array_diff($allFields ?? [], $blackList);
    }

    /**
     * @param array|null $context
     * @return ResolverReference
     */
    public function getDefaultResolver(?array $context = []): ResolverReference
    {
        $callable = empty($context)
            ? [Resolver::class, 'resolve']
            : [Resolver::class, 'resolveContext'];

        return ResolverReference::create($callable);
    }

    /**
     * @return string
     */
    public function getSourceClass(): string
    {
        return get_class($this->dataObject);
    }

    /**
     * @return FieldAccessor
     */
    public function getFieldAccessor(): FieldAccessor
    {
        return $this->fieldAccessor;
    }

    /**
     * @param FieldAccessor $fieldAccessor
     * @return DataObjectModel
     */
    public function setFieldAccessor(FieldAccessor $fieldAccessor): self
    {
        $this->fieldAccessor = $fieldAccessor;
        return $this;
    }

    /**
     * @param string $id
     * @return OperationCreator|null
     * @throws SchemaBuilderException
     */
    public function getOperationCreatorByIdentifier(string $id): ?OperationCreator
    {
        $registeredOperations = $this->getModelConfiguration()->get('operations', []);
        $creator = $registeredOperations[$id]['class'] ?? null;
        if (!$creator) {
            return null;
        }
        Schema::invariant(
            class_exists($creator ?? ''),
            'Operation creator %s does not exist',
            $creator
        );
        /* @var OperationCreator $obj */
        $obj = Injector::inst()->get($creator);
        Schema::invariant(
            $obj instanceof OperationCreator,
            'Operation %s is not an instance of %s',
            $creator,
            OperationCreator::class
        );

        return $obj;
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getAllOperationIdentifiers(): array
    {
        $registeredOperations = $this->getModelConfiguration()->get('operations', []);

        return array_keys($registeredOperations ?? []);
    }

    /**
     * Gets a field that resolves to another model, (e.g. an ObjectType from a has_one).
     * This method can be used to determine *if* a field is another model, and also to
     * get that field.
     *
     * @param string $fieldName
     * @param string $class Optional class name for model fields which would result in database queries.
     *                      The database is not always available when the schema is built (e.g. on deployment servers).
     * @return ModelType|null
     * @throws SchemaBuilderException
     */
    public function getModelTypeForField(string $fieldName, $class = null): ?ModelType
    {
        if (!$class) {
            $result = $this->getFieldAccessor()->accessField($this->dataObject, $fieldName);
            $class = $this->getModelClass($result);
        }

        if (!$class) {
            return null;
        }

        $model = $this->getSchemaConfig()->createModel($class);
        if (!$model) {
            return null;
        }

        return ModelType::create($model);
    }

    /**
     * @param string $field
     * @return string
     */
    public function getPropertyForField(string $field): string
    {
        $sng = DataObject::singleton($this->getSourceClass());

        return FieldAccessor::singleton()->normaliseField($sng, $field) ?: $field;
    }

    /**
     * @return DataObject
     */
    public function getDataObject(): DataObject
    {
        return $this->dataObject;
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     */
    public function getTypeName(): string
    {
        $class = get_class($this->dataObject);
        $mapping = $this->getSchemaConfig()->getTypeMapping();
        if (isset($mapping[$class])) {
            return $mapping[$class];
        }

        return $this->getModelConfiguration()->getTypeName($class);
    }

    /**
     * @return SchemaConfig
     */
    public function getSchemaConfig(): SchemaConfig
    {
        return $this->schemaConfig;
    }

    /**
     * @return ModelConfiguration|null
     * @throws SchemaBuilderException
     */
    public function getModelConfiguration(): ?ModelConfiguration
    {
        return $this->getSchemaConfig()->getModelConfiguration(static::getIdentifier());
    }

    /**
     * @param $result
     * @return string|null
     */
    private function getModelClass($result): ?string
    {
        if ($result instanceof DataObject) {
            return get_class($result);
        }
        if ($result instanceof SS_List && method_exists($result, 'dataClass')) {
            return $result->dataClass();
        }

        return null;
    }

    /**
     * @param $result
     * @return bool
     */
    private function isList($result): bool
    {
        return $result instanceof SS_List || $result instanceof UnsavedRelationList;
    }

    /**
     * @param ModelField $field
     * @param string | null $class
     * @throws SchemaBuilderException
     */
    private function applyMetadataClass(ModelField $field, ?string $class = null): void
    {
        $field->getMetadata()
            ->set('dataClass', $class);
    }
}
