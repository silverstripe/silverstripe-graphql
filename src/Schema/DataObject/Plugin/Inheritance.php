<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceChain;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\ModelUnionType;
use SilverStripe\ORM\DataObject;
use ReflectionException;
use Exception;

/**
 * Adds inheritance fields to a DataObject type, and exposes its ancestry
 */
class Inheritance implements PluginInterface, SchemaUpdater
{
    const IDENTIFIER = 'inheritance';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param Schema $schema
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    public static function updateSchema(Schema $schema): void
    {
        $baseModels = [];
        $leafModels = [];
        foreach (self::getDataObjectTypes($schema) as $modelType) {
            $class = $modelType->getModel()->getSourceClass();
            if (self::isBaseModel($class, $schema)) {
                $baseModels[] = $modelType;
            } else if (self::isLeafModel($class, $schema)) {
                $leafModels[] = $modelType;
            }
        }
        foreach ($leafModels as $modelType) {
            self::fillAncestry($schema, $modelType);
        }
        foreach ($baseModels as $modelType) {
            self::fillDescendants($schema, $modelType);
            self::createInterfaces($schema, $modelType);
        }

        self::applyBaseInterface($schema);

        self::createUnions($schema);
        self::applyUnions($schema);

    }

    /**
     * @param $obj
     * @param $context
     * @return string
     * @throws SchemaBuilderException
     * @throws Exception
     */
    public static function resolveType($obj, $context): string
    {
        $class = get_class($obj);
        $schemaContext = SchemaConfigProvider::get($context);

        while ($class && !$schemaContext->hasModel($class)) {
            if ($class === DataObject::class) {
                throw new Exception(sprintf(
                    'No models were registered in the ancestry of %s',
                    get_class($obj)
                ));
            }
            $class = get_parent_class($class);
            Schema::invariant(
                $class,
                'Could not resolve type for %s.',
                get_class($obj)
            );
        }
        return $schemaContext->getTypeNameForClass($class);
    }

    /**
     * @param string $modelName
     * @param SchemaConfig $schemaConfig
     * @return string
     */
    public static function unionName(string $modelName, SchemaConfig $schemaConfig): string
    {
        $callable = $schemaConfig->get(
            'inheritance.union_formatter',
            [static:: class, 'defaultUnionFormatter']
        );
        return $callable($modelName);
    }

    /**
     * @param string $modelName
     * @param SchemaConfig $schemaConfig
     * @return string
     */
    public static function interfaceName(string $modelName, SchemaConfig $schemaConfig): string
    {
        $callable = $schemaConfig->get(
            'inheritance.interface_formatter',
            [static:: class, 'defaultInterfaceFormatter']
        );
        return $callable($modelName);
    }

    /**
     * @param string $modelName
     * @return string
     */
    public static function defaultUnionFormatter(string $modelName): string
    {
        return $modelName . 'InheritanceUnion';
    }

    /**
     * @param string $modelName
     * @return string
     */
    public static function defaultInterfaceFormatter(string $modelName): string
    {
        return $modelName . 'Interface';
    }

    /**
     * @param Schema $schema
     * @return ModelType[]
     */
    private static function getDataObjectTypes(Schema $schema): array
    {
        return array_filter($schema->getModels(), function (ModelType $modelType) {
            $class = $modelType->getModel()->getSourceClass();
            return is_subclass_of($class, DataObject::class);
        });
    }

    /**
     * @param Schema $schema
     * @return array
     */
    private static function getDataObjectInterfaces(Schema $schema): array
    {
        return array_filter($schema->getInterfaces(), function (InterfaceType $interface) {
            if ($interface instanceof ModelInterfaceType) {
                $class = $interface->getModel()->getSourceClass();
                return is_subclass_of($class, DataObject::class);
            }
            return false;
        });
    }

    /**
     * @param Schema $schema
     * @param ModelType $modelType
     * @throws SchemaBuilderException
     */
    private static function fillAncestry(Schema $schema, ModelType $modelType): void
    {
        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass());
        $ancestors = $chain->getAncestralModels();
        if (empty($ancestors)) {
            return;
        }
        $parent = $ancestors[0];
        $parentModel = $schema->findOrMakeModel($parent);
        // Merge descendant fields up into the ancestor
        foreach ($modelType->getFields() as $fieldObj) {
            // If the field already exists on the ancestor, skip it
            if ($parentModel->getFieldByName($fieldObj->getName())) {
                continue;
            }
            $fieldName = $fieldObj instanceof ModelField
                ? $fieldObj->getPropertyName()
                : $fieldObj->getName();
            // If the field is unique to the descendant, skip it.
            if ($parentModel->getModel()->hasField($fieldName)) {
                $clone = clone $fieldObj;
                $parentModel->addField($fieldObj->getName(), $clone);
            }
        }
        self::fillAncestry($schema, $parentModel);
    }

    /**
     * @param Schema $schema
     * @param ModelType $modelType
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    private static function fillDescendants(Schema $schema, ModelType $modelType)
    {
        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass());
        $descendants = $chain->getDirectDescendants();
        if (empty($descendants)) {
            return;
        }
        foreach ($descendants as $descendant) {
            $descendantModel = $schema->getModelByClassName($descendant);
            if ($descendantModel) {
                foreach ($modelType->getFields() as $fieldObj) {
                    if ($descendantModel->getFieldByName($fieldObj->getName())) {
                        continue;
                    }
                    $clone = clone $fieldObj;
                    $descendantModel->addField($fieldObj->getName(), $clone);
                }
                self::fillDescendants($schema, $descendantModel);
            }
        }
    }

    /**
     * @param Schema $schema
     * @param ModelType $modelType
     * @param ModelInterfaceType[] $interfaceStack
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    private static function createInterfaces(
        Schema $schema,
        ModelType $modelType,
        array $interfaceStack = []
    ): void {

        $interface = ModelInterfaceType::create(
            $modelType->getModel(),
            self::interfaceName($modelType->getName(), $schema->getConfig())
        );
        $interface->setTypeResolver([static::class, 'resolveType']);

        // Start by adding all the fields in the model
        foreach ($modelType->getFields() as $fieldObj) {
            $clone = clone $fieldObj;
            $interface->addField($fieldObj->getName(), $clone);
        }

        // Remove any fields that are exposed in ancestors, ensuring
        // each interface only contains "native" fields
        foreach ($interfaceStack as $ancestorInterface) {
            foreach ($ancestorInterface->getFields() as $fieldObj) {
                $interface->removeField($fieldObj->getName());
            }
        }

        // If the interface has no fields, just skip it and proceed down the tree
        if (!empty($interface->getFields())) {
            $schema->addInterface($interface);
            $interfaceStack[] = $interface;
        }
        foreach ($interfaceStack as $interface) {
            $modelType->addInterface($interface->getName());
        }

        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass());
        foreach ($chain->getDirectDescendants() as $class) {
            if ($childType = $schema->getModelByClassName($class)) {
                self::createInterfaces($schema, $childType, $interfaceStack);
            }
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    private static function applyBaseInterface(Schema $schema): void
    {
        $commonFields = $schema->getConfig()
            ->getModelConfiguration('DataObject')
            ->getBaseFields();

        if (empty($commonFields)) {
            return;
        }
        $baseInterface = InterfaceType::create('DataObject');
        foreach ($commonFields as $fieldName => $fieldType) {
            $baseInterface->addField(
                FieldAccessor::singleton()->formatField($fieldName),
                $fieldType
            );
        }
        $baseInterface->setDescription('The common interface shared by all DataObject types');
        $baseInterface->setTypeResolver([static::class, 'resolveType']);
        $schema->addInterface($baseInterface);

        foreach (self::getDataObjectTypes($schema) as $modelType) {
            $modelType->addInterface($baseInterface->getName());
        }
    }

    /**
     * @param Schema $schema
     */
    private static function createUnions(Schema $schema)
    {
        /* @var ModelInterfaceType $interface */
        foreach (self::getDataObjectTypes($schema) as $modelType) {
            $chain = InheritanceChain::create($modelType->getModel()->getSourceClass());

            $name = static::unionName($modelType->getName(), $schema->getConfig());
            $union = ModelUnionType::create($modelType, $name);

            $types = array_map(function ($class) use ($schema) {
                return $schema->getConfig()->getTypeNameForClass($class);
            }, $chain->getInheritance());

            $types[] = $modelType->getName();

            $union->setTypes($types);
            $union->setTypeResolver([static::class, 'resolveType']);
            $schema->addUnion($union);
        }
    }

    /**
     * @param Schema $schema
     * @throws SchemaBuilderException
     */
    private static function applyUnions(Schema $schema): void
    {
        $queries = [];
        foreach ($schema->getQueryType()->getFields() as $field) {
            if ($field instanceof ModelQuery) {
                $queries[] = $field;
            }
        }
        foreach ($schema->getModels() as $model) {
            foreach ($model->getFields() as $field) {
                if ($field instanceof ModelQuery) {
                    $queries[] = $field;
                }
            }
        }
        foreach ($schema->getInterfaces() as $interface) {
            if (!$interface instanceof ModelInterfaceType) {
                continue;
            }
            foreach ($interface->getFields() as $field) {
                if ($field instanceof ModelQuery) {
                    $queries[] = $field;
                }
            }
        }
        /* @var ModelQuery $query */
        foreach ($queries as $query) {
            $typeName = $query->getNamedType();
            $modelType = $schema->getModel($typeName);
            // Type was customised. Ignore.
            if (!$modelType) {
                continue;
            }
            if (!$modelType->getModel() instanceof DataObjectModel) {
                continue;
            }

            $unionName = static::unionName($modelType->getName(), $schema->getConfig());
            if ($union = $schema->getUnion($unionName)) {
                $query->setNamedType($unionName);
            }
        }
    }

    /**
     * @param Schema $schema
     * @throws SchemaBuilderException
     */
    private static function applyInterfaces(Schema $schema): void
    {
        $queries = [];
        foreach ($schema->getQueryType()->getFields() as $field) {
            if ($field instanceof ModelQuery) {
                $queries[] = $field;
            }
        }
        foreach ($schema->getModels() as $model) {
            foreach ($model->getFields() as $field) {
                if ($field instanceof ModelQuery) {
                    $queries[] = $field;
                }
            }
        }
        foreach ($schema->getInterfaces() as $interface) {
            if (!$interface instanceof ModelInterfaceType) {
                continue;
            }
            foreach ($interface->getFields() as $field) {
                if ($field instanceof ModelQuery) {
                    $queries[] = $field;
                }
            }
        }
        /* @var ModelQuery $query */
        foreach ($queries as $query) {
            $typeName = $query->getNamedType();
            $modelType = $schema->getModel($typeName);
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
            }
        }
    }

    /**
     * @param string $class
     * @param Schema $schema
     * @return bool
     */
    private static function isBaseModel(string $class, Schema $schema): bool
    {
        $chain = InheritanceChain::create($class);
        if ($chain->getBaseClass() === $class) {
            return true;
        }
        foreach ($chain->getAncestralModels() as $class) {
            if ($schema->getModelByClassName($class)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $class
     * @return bool
     * @throws ReflectionException
     */
    private static function isStandaloneModel(string $class): bool
    {
        return !InheritanceChain::create($class)->hasInheritance();
    }

    /**
     * @param Schema $schema
     * @param string $class
     * @return bool
     */
    private static function isReadable(Schema $schema, string $class): bool
    {
        $type = $schema->getModelByClassName($class);
        if (!$type) {
            return false;
        }
        foreach ($type->getOperations() as $operation) {
            if ($operation instanceof ModelQuery) {
                return true;
            }
        }
        // Check for nested queries that expose the object
        foreach ($schema->getModels() as $model) {
            foreach ($model->getFields() as $field) {
                if ($field->getNamedType() === $type->getName()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $class
     * @param Schema $schema
     * @return bool
     * @throws ReflectionException
     */
    private static function isLeafModel(string $class, Schema $schema): bool
    {
        $chain = InheritanceChain::create($class);
        foreach ($chain->getDescendantModels() as $class) {
            if ($schema->getModelByClassName($class)) {
                return false;
            }
        }
        return true;
    }
}
