<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\Inheritance;
use SilverStripe\GraphQL\Schema\Interfaces\DefaultFieldsProvider;
use SilverStripe\GraphQL\Schema\Interfaces\DefaultPluginProvider;
use SilverStripe\GraphQL\Schema\Interfaces\ExtraTypeProvider;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Interfaces\ModelDependencyProvider;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Interfaces\OperationProvider;
use SilverStripe\GraphQL\Schema\Interfaces\RequiredFieldsProvider;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Registry\SchemaModelCreatorRegistry;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use ReflectionException;

class DataObjectModel implements
    SchemaModelInterface,
    OperationProvider,
    DefaultFieldsProvider,
    DefaultPluginProvider
{
    use Injectable;
    use Configurable;

    /**
     * @var array
     * @config
     */
    private static $default_plugins = [];

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
     * @return string|null
     * @throws SchemaBuilderException
     */
    public function getTypeForField(string $fieldName): ?string
    {
        $result = $this->getFieldAccessor()->accessField($this->dataObject, $fieldName);
        if (!$result) {
            return null;
        }
        if ($result instanceof DBField) {
            return $result->config()->get('graphql_type');
        }
        $class = $this->getModelClass($result);
        Schema::invariant(
            $class,
            'Cannot determine data class for field %s on %s',
            $fieldName,
            get_class($this->dataObject)
        );

        $type = DataObjectModel::create($class)->getTypeName();

        return sprintf('[%s]', $type);
    }

    /**
     * @return array
     */
    public function getDefaultFields(): array
    {
        return [
            'id' => 'ID',
        ];
    }

    /**
     * @return array
     */
    public function getDefaultPlugins(): array
    {
        return $this->config()->get('default_plugins');
    }

    /**
     * @return array
     */
    public function getAllFields(): array
    {
        return $this->getFieldAccessor()->getAllFields($this->dataObject);
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
     * @param array|null $config
     * @return OperationCreator|null
     * @throws SchemaBuilderException
     */
    public function getOperationCreatorByIdentifier(string $id, ?array $config = null): ?OperationCreator
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
     * @param string $fieldName
     * @return ModelType|null
     */
    public function getModelField(string $fieldName): ?ModelType
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
