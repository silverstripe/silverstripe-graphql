<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryCollector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\TypePlugin;
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
     * @var array
     */
    private $hideAncestors = [];

    /**
     * InterfaceBuilderTest constructor.
     * @param Schema $schema
     * @param array $hideAncestors
     */
    public function __construct(Schema $schema, array $hideAncestors = [])
    {
        $this->setSchema($schema);
        $this->hideAncestors = $hideAncestors;
    }

    /**
     * @param ModelType $modelType
     * @param ModelInterfaceType[] $interfaceStack
     * @throws ReflectionException
     * @throws SchemaBuilderException
     * @return $this
     */
    public function createInterfaces(ModelType $modelType, array $interfaceStack = []): InterfaceBuilder
    {
        $interface = ModelInterfaceType::create(
            $modelType,
            self::interfaceName($modelType->getName(), $this->getSchema()->getConfig())
        )
            ->setTypeResolver([AbstractTypeResolver::class, 'resolveType']);

        // TODO: this makes a really good case for
        // https://github.com/silverstripe/silverstripe-graphql/issues/364
        $validPlugins = [];
        foreach ($modelType->getPlugins() as $name => $config) {
            $plugin = $modelType->getPluginRegistry()->getPluginByID($name);
            if ($plugin && $plugin instanceof TypePlugin) {
                $validPlugins[$name] = $config;
            }
        }
        $interface->setPlugins($validPlugins);


        // Start by adding all the fields in the model
        foreach ($modelType->getFields() as $fieldObj) {
            // Assign by reference, because anything that happens to the field
            // should be updated in both places to avoid breaking the contract
            $interface->addField($fieldObj->getName(), $fieldObj);
        }

        $this->getSchema()->addInterface($interface);

        foreach ($interfaceStack as $ancestorInterface) {
            $modelType->addInterface($ancestorInterface->getName());
        }

        $interfaceStack[] = $interface;
        $modelType->addInterface($interface->getName());

        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass())
            ->hideAncestors($this->hideAncestors);

        foreach ($chain->getDirectDescendants() as $class) {
            if ($childType = $this->getSchema()->getModelByClassName($class)) {
                $this->createInterfaces($childType, $interfaceStack);
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws SchemaBuilderException
     */
    public function applyBaseInterface(): InterfaceBuilder
    {
        $commonFields = $this->getSchema()->getConfig()
            ->getModelConfiguration('DataObject')
            ->getBaseFields();

        if (empty($commonFields)) {
            return $this;
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

        return $this;
    }

    /**
     * @param ModelType $type
     * @throws ReflectionException
     * @throws SchemaBuilderException
     * @return $this
     */
    public function applyInterfacesToQueries(ModelType $type): InterfaceBuilder
    {
        $schema = $this->getSchema();
        $queryCollector = QueryCollector::create($schema);
        /* @var ModelQuery $query */
        foreach ($queryCollector->collectQueriesForType($type) as $query) {
            $typeName = $query->getNamedType();
            $modelType = $this->getSchema()->getModel($typeName);
            // Type was customised. Ignore.
            if (!$modelType) {
                continue;
            }
            if (!$modelType->getModel() instanceof DataObjectModel) {
                continue;
            }

            $interfaceName = static::interfaceName($modelType->getName(), $schema->getConfig());
            if ($interface = $schema->getInterface($interfaceName)) {
                $query->setNamedType($interfaceName);
                // Because the canonical type no longer appears in a query, we need to eagerly load
                // it into the schema so it is discoverable. Helps with intellisense
                $this->schema->eagerLoad($modelType->getName());
            }
        }
        $chain = InheritanceChain::create($type->getModel()->getSourceClass())
            ->hideAncestors($this->hideAncestors);

        foreach ($chain->getDirectDescendants() as $class) {
            if ($modelType = $schema->getModelByClassName($class)) {
                $this->applyInterfacesToQueries($modelType);
            }
        }

        return $this;
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
