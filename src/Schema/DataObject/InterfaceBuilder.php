<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use ReflectionException;
use SilverStripe\ORM\DataObject;

/**
 * A schema-aware service for DataObject model types that emulates class inheritance
 * by capturing groups of common fields into interfaces and applying one or many
 * interfaces to concrete model types. Also creates a "base" interface for fields
 * common to all DataObjects (i.e. "extends DataObject" pattern)
 */
class InterfaceBuilder
{
    use Injectable;

    const BASE_INTERFACE_NAME = 'DataObject';

    /**
     * @var Schema
     */
    private $schema;

    /**
     * InterfaceBuilderTest constructor.
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->setSchema($schema);
    }

    /**
     * @param ModelType $modelType
     * @param ModelInterfaceType[] $interfaceStack
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    public function createInterfaces(ModelType $modelType, array $interfaceStack = []): void {
        $interface = ModelInterfaceType::create(
            $modelType->getModel(),
            self::interfaceName($modelType->getName(), $this->getSchema()->getConfig())
        );
        $interface->setTypeResolver([AbstractTypeResolver::class, 'resolveType']);

        // Start by adding all the fields in the model
        foreach ($modelType->getFields() as $fieldObj) {
            $clone = clone $fieldObj;
            $interface->addField($fieldObj->getName(), $clone);
        }

        $this->getSchema()->addInterface($interface);

        foreach ($interfaceStack as $ancestorInterface) {
            $modelType->addInterface($ancestorInterface->getName());
            $interface->addInterface($ancestorInterface->getName());
        }

        $interfaceStack[] = $interface;
        $modelType->addInterface($interface->getName());

        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass());
        foreach ($chain->getDirectDescendants() as $class) {
            if ($childType = $this->getSchema()->getModelByClassName($class)) {
                $this->createInterfaces($childType, $interfaceStack);
            }
        }
    }

    /**
     * @return void
     * @throws SchemaBuilderException
     */
    public function applyBaseInterface(): void
    {
        $commonFields = $this->getSchema()->getConfig()
            ->getModelConfiguration('DataObject')
            ->getBaseFields();

        if (empty($commonFields)) {
            return;
        }
        $baseInterface = InterfaceType::create(self::BASE_INTERFACE_NAME);
        foreach ($commonFields as $fieldName => $fieldType) {
            $baseInterface->addField(
                FieldAccessor::singleton()->formatField($fieldName),
                $fieldType
            );
        }
        $baseInterface->setDescription('The common interface shared by all DataObject types');
        $baseInterface->setTypeResolver([AbstractTypeResolver::class, 'resolveType']);
        $this->getSchema()->addInterface($baseInterface);

        $dataObjects = $this->getSchema()->getModelTypesFromClass(DataObject::class);
        foreach ($dataObjects as $modelType) {
            $modelType->addInterface($baseInterface->getName());
        }
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @param Schema $schema
     * @return InterfaceBuilder
     */
    public function setSchema(Schema $schema): InterfaceBuilder
    {
        $this->schema = $schema;
        return $this;
    }


    /**
     * @param string $modelName
     * @param SchemaConfig $schemaConfig
     * @return string
     * @throws SchemaBuilderException
     */
    public static function interfaceName(string $modelName, SchemaConfig $schemaConfig): string
    {
        $callable = $schemaConfig->get(
            'interfaceBuilder.name_formatter',
            [static:: class, 'defaultInterfaceFormatter']
        );
        return $callable($modelName);
    }

    /**
     * @param string $modelName
     * @return string
     */
    public static function defaultInterfaceFormatter(string $modelName): string
    {
        return $modelName . 'Interface';
    }

}
