<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Config\ModelConfiguration;
use SilverStripe\GraphQL\Dev\BuildState;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\DefaultFieldsProvider;
use SilverStripe\GraphQL\Schema\Interfaces\ModelBlacklist;
use SilverStripe\GraphQL\Schema\Interfaces\ModelConfigurationProvider;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
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
    ModelBlacklist,
    ModelConfigurationProvider
{
    use Injectable;
    use Configurable;

    /**
     * @var ModelConfiguration
     */
    private $configuration;

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
     * @param ModelConfiguration|null $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $class, ?ModelConfiguration $config = null)
    {
        Schema::invariant(
            is_subclass_of($class, DataObject::class),
            '%s only accepts %s subclasses',
            static::class,
            DataObject::class
        );
        $this->dataObject = Injector::inst()->get($class);
        $this->configuration = $config;
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

        if ($result instanceof DBField) {
            $fieldConfig = array_merge([
                'type' => $result->config()->get('graphql_type'),
            ], $config);

            return ModelField::create($fieldName, $fieldConfig, $this);
        }

        $class = $this->getModelClass($result);
        if (!$class) {
            if ($this->isList($result)) {
                return ModelField::create($fieldName, $config, $this);
            }
            return null;
        }
        $type = BuildState::requireActiveBuild()->getTypeNameForClass($class);
        if ($this->isList($result)) {
            $queryConfig = array_merge([
                'type' => sprintf('[%s]', $type),
            ], $config);
            $query = ModelQuery::create($this, $fieldName, $queryConfig);
            $query->setDefaultPlugins($this->getModelConfig()->getNestedQueryPlugins());
            return $query;
        }
        return ModelField::create($fieldName, $type, $this);
    }

    /**
     * @return array
     */
    public function getDefaultFields(): array
    {
        $idField = $this->getFieldAccessor()->formatField('ID');
        return [
            $idField => 'ID',
        ];
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
        }, $blackList);
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getAllFields(): array
    {
        $blackList = $this->getBlacklistedFields();
        $allFields = $this->getFieldAccessor()->getAllFields($this->dataObject);

        return array_diff($allFields, $blackList);
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getUninheritedFields(): array
    {
        $blackList = $this->getBlacklistedFields();
        $allFields = $this->getFieldAccessor()->getAllFields($this->dataObject, true, false);

        return array_diff($allFields, $blackList);
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
        $registeredOperations = $this->getModelConfig()->get('operations', []);
        $creator = $registeredOperations[$id]['class'] ?? null;
        if (!$creator) {
            return null;
        }
        Schema::invariant(
            class_exists($creator),
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
     * @return string[]
     * @throws SchemaBuilderException
     */
    public function getAllOperationIdentifiers(): array
    {
        $registeredOperations = $this->getModelConfig()->get('operations', []);

        return array_keys($registeredOperations);
    }

    /**
     * Gets a field that resolves to another model, (e.g. an ObjectType from a has_one).
     * This method can be used to determine *if* a field is another model, and also to
     * get that field.
     *
     * @param string $fieldName
     * @return ModelType|null
     * @throws SchemaBuilderException
     */
    public function getModelTypeForField(string $fieldName): ?ModelType
    {
        $result = $this->getFieldAccessor()->accessField($this->dataObject, $fieldName);
        $class = $this->getModelClass($result);
        if (!$class) {
            return null;
        }

        return BuildState::requireActiveBuild()->createModel($class);
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
        return $this->getModelConfig()->getTypeName(get_class($this->dataObject));
    }

    /**
     * @return ModelConfiguration
     */
    public function getModelConfig(): ModelConfiguration
    {
        return $this->configuration;
    }

    /**
     * @param ModelConfiguration $configuration
     */
    public function applyModelConfig(ModelConfiguration $configuration): void
    {
        $this->configuration = $configuration;
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
}
