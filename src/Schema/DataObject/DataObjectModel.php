<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\DefaultFieldsProvider;
use SilverStripe\GraphQL\Schema\ExtraTypeProvider;
use SilverStripe\GraphQL\Schema\ModelAbstraction;
use SilverStripe\GraphQL\Schema\ModelDependencyProvider;
use SilverStripe\GraphQL\Schema\OperationCreator;
use SilverStripe\GraphQL\Schema\OperationProvider;
use SilverStripe\GraphQL\Schema\RequiredFieldsProvider;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\SchemaModelCreatorRegistry;
use SilverStripe\GraphQL\Schema\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\TypeAbstraction;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use ReflectionException;

class DataObjectModel implements
    SchemaModelInterface,
    OperationProvider,
    DefaultFieldsProvider,
    RequiredFieldsProvider,
    ExtraTypeProvider,
    ModelDependencyProvider
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
     * @var InheritanceChain
     */
    private $inheritanceChain;

    /**
     * DataObjectModel constructor.
     * @param string $class
     * @throws SchemaBuilderException
     */
    public function __construct(string $class)
    {
        SchemaBuilder::invariant(
            is_subclass_of($class, DataObject::class),
            '%s only accepts %s subclasses',
            static::class,
            DataObject::class
        );
        $this->dataObject = Injector::inst()->get($class);
        $this->setInheritanceChain(InheritanceChain::create($this->dataObject));
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
        if ($fieldName === $this->getInheritanceChain()->getName()) {
            return true;
        }
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
        SchemaBuilder::invariant(
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
     * @throws ReflectionException
     */
    public function getRequiredFields(): array
    {
        $descendants = $this->getInheritanceChain()->getDescendantModels();
        if (empty($descendants)) {
            return [];
        }
        $descendantsType = $this->getInheritanceChain()->getExtensionType();

        return [
            $this->getInheritanceChain()->getName() => $descendantsType->getName()
        ];
    }

    /**
     * @return TypeAbstraction[]
     * @throws ReflectionException
     */
    public function getExtraTypes(): array
    {
        $types = [];
        if ($extensionType = $this->getInheritanceChain()->getExtensionType()) {
            $types[] = $this->getInheritanceChain()->getExtensionType();
        }

        return $types;
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
     * @return array
     */
    public function getDefaultResolver(?array $context = []): array
    {
        return empty($context)
            ? [Resolver::class, 'resolve']
            : [Resolver::class, 'resolveContext'];

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
    public function setFieldAccessor(FieldAccessor $fieldAccessor): DataObjectModel
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
        SchemaBuilder::invariant(
            class_exists($creator),
            'Operation creator %s does not exist'
        );
        /* @var OperationCreator $obj */
        $obj = Injector::inst()->get($creator);
        SchemaBuilder::invariant(
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
     * @return ModelAbstraction|null
     */
    public function getModelField(string $fieldName): ?ModelAbstraction
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

        return ModelAbstraction::create($class);
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getModelDependencies(): array
    {
        return array_merge(
            $this->getInheritanceChain()->getAncestralModels(),
            $this->getInheritanceChain()->getDescendantModels()
        );
    }

    /**
     * @return InheritanceChain
     */
    public function getInheritanceChain(): InheritanceChain
    {
        return $this->inheritanceChain;
    }

    /**
     * @param InheritanceChain $inheritanceChain
     * @return DataObjectModel
     */
    public function setInheritanceChain(InheritanceChain $inheritanceChain): DataObjectModel
    {
        $this->inheritanceChain = $inheritanceChain;
        return $this;
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
        SchemaBuilder::invariant(
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

        SchemaBuilder::invariant(
            is_string($prefix),
            'type_prefix on %s must be a string',
            __CLASS__
        );

        return $prefix;
    }

}
