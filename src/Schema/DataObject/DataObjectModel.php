<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\DefaultFieldsProvider;
use SilverStripe\GraphQL\Schema\Interfaces\ModelBlacklist;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\OperationProvider;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Registry\SchemaModelCreatorRegistry;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;

/**
 * Defines the model that generates types, queries, and mutations based on DataObjects
 */
class DataObjectModel implements
    SchemaModelInterface,
    OperationProvider,
    DefaultFieldsProvider,
    ModelBlacklist
{
    use Injectable;
    use Configurable;

    /**
     * @var callable
     * @config
     */
    private static $type_formatter = [ ClassInfo::class, 'shortName' ];

    /**
     * @var string
     */
    private static $type_prefix = '';

    /**
     * @var array
     * @config
     */
    private static $type_mapping = [];

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
     * @throws SchemaBuilderException
     */
    public function __construct(string $class)
    {
        Schema::invariant(
            is_subclass_of($class, DataObject::class),
            '%s only accepts %s subclasses',
            static::class,
            DataObject::class
        );
        $this->dataObject = Injector::inst()->get($class);
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     */
    public function getTypeName(): string
    {
        $class = get_class($this->dataObject);
        $mapping = $this->config()->get('type_mapping');
        $custom = $mapping[$class] ?? null;
        if ($custom) {
            return $custom;
        }

        $typeName = $this->formatClass($class);
        $prefix = $this->getPrefix($class);

        return $prefix . $typeName;
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
     * @return ModelField|null
     * @throws SchemaBuilderException
     */
    public function getField(string $fieldName): ?ModelField
    {
        $result = $this->getFieldAccessor()->accessField($this->dataObject, $fieldName);
        if (!$result) {
            return null;
        }
        if ($result instanceof DBField) {
            return ModelField::create(
                $fieldName,
                $result->config()->get('graphql_type'),
                $this
            );
        }
        $class = $this->getModelClass($result);
        Schema::invariant(
            $class,
            'Cannot determine data class for field %s on %s',
            $fieldName,
            get_class($this->dataObject)
        );

        $type = DataObjectModel::create($class)->getTypeName();
        if ($this->isList($result)) {
            $queryConfig = [
                'plugins' => [],//$this->getNestedDefaultPlugins(),
                'type' => sprintf('[%s]', $type),
            ];
            return ModelQuery::create($this, $fieldName, $queryConfig);
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
        $registeredOperations = $this->config()->get('operations') ?? [];
        $creator = $registeredOperations[$id] ?? null;
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
     */
    public function getAllOperationIdentifiers(): array
    {
        $registeredOperations = $this->config()->get('operations') ?? [];

        return array_keys($registeredOperations);
    }

    /**
     * Gets a field that resolves to another model, (e.g. an ObjectType from a has_one).
     * This method can be used to determine *if* a field is another model, and also to
     * get that field.
     *
     * @param string $fieldName
     * @return ModelType|null
     */
    public function getModelTypeForField(string $fieldName): ?ModelType
    {
        $result = $this->getFieldAccessor()->accessField($this->dataObject, $fieldName);
        $class = $this->getModelClass($result);
        if (!$class) {
            return null;
        }
        $model = SchemaModelCreatorRegistry::singleton()->getModel($class);
        if (!$model) {
            return null;
        }

        return ModelType::create($class);
    }

    /**
     * @return DataObject
     */
    public function getDataObject(): DataObject
    {
        return $this->dataObject;
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
        return $result instanceof SS_List;
    }

    /**
     * @param string $class
     * @return string
     * @throws SchemaBuilderException
     */
    private function formatClass(string $class): string
    {
        $formatter = $this->config()->get('type_formatter');
        Schema::invariant(
            is_callable($formatter, false),
            'type_formatter property for %s is not callable',
            __CLASS__
        );

        return call_user_func_array($formatter, [$class]);
    }

    /**
     * @param string $class
     * @return string
     * @throws SchemaBuilderException
     */
    private function getPrefix(string $class): string
    {
        $prefix = $this->config()->get('type_prefix');
        if (is_callable($prefix, false)) {
            return call_user_func_array($prefix, [$class]);
        }

        Schema::invariant(
            is_string($prefix),
            'type_prefix on %s must be a string',
            __CLASS__
        );

        return $prefix;
    }

}
