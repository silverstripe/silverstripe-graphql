<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Core\ClassInfo;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\QueryHandler\SchemaContextProvider;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceChain;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\ModelUnionType;
use SilverStripe\ORM\DataObject;
use ReflectionException;
use Exception;
use SilverStripe\ORM\SS_List;

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
            if (!is_subclass_of($class, DataObject::class)) {
                continue;
            }
            if (self::isBaseModel($class, $schema)) {
                $baseModels[] = $class;
            } else if (self::isLeafModel($class, $schema)) {
                $leafModels[] = $class;
            }
        }

        // Ensure the ancestry of every descendant is exposed
        foreach ($leafModels as $leafClass) {
            self::fillAncestry($schema, $leafClass);
        }
        foreach ($baseModels as $class) {
            self::fillDescendants($schema, $class);
            self::createInterfaces($schema, $class);
        }

        self::applyBaseInterface($schema, $baseModels);

        self::createUnions($schema);
        if ($schema->getConfig()->get('inheritance.useUnionQueries')) {
            self::applyUnions($schema);
        }
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
     * @return string
     */
    public static function interfaceName(string $modelName): string
    {
        return $modelName . 'Interface';
    }

    /**
     * @param string $modelName
     * @return string
     */
    public static function unionName(string $modelName): string
    {
        return $modelName . 'InheritanceUnion';
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
     * @param string $class
     * @throws SchemaBuilderException
     */
    private static function fillAncestry(Schema $schema, string $class): void
    {
        $chain = InheritanceChain::create($class);
        $model = $schema->getModelByClassName($class);
        $ancestors = $chain->getAncestralModels();
        if (empty($ancestors)) {
            return;
        }
        $parent = $ancestors[0];
        $parentModel = $schema->findOrMakeModel($parent);
        // Merge descendant fields up into the ancestor
        foreach ($model->getFields() as $fieldObj) {
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
        self::fillAncestry($schema, $parent);
    }

    private static function fillDescendants(Schema $schema, string $class)
    {
        $chain = InheritanceChain::create($class);
        $model = $schema->getModelByClassName($class);
        $descendants = $chain->getDirectDescendants();
        if (empty($descendants)) {
            return;
        }
        foreach ($descendants as $descendant) {
            $descendantModel = $schema->getModelByClassName($descendant);
            if ($descendantModel) {
                foreach ($model->getFields() as $fieldObj) {
                    if ($descendantModel->getFieldByName($fieldObj->getName())) {
                        continue;
                    }
                    $clone = clone $fieldObj;
                    $descendantModel->addField($fieldObj->getName(), $clone);
                }
                self::fillDescendants($schema, $descendant);
            }
        }
    }

    /**
     * @param Schema $schema
     * @param string $class
     * @param ModelType[] $modelStack
     * @param ModelInterfaceType[] $interfaceStack
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    private static function createInterfaces(
        Schema $schema,
        string $class,
        array $modelStack = [],
        array $interfaceStack = []
    ) {
        $modelType = $schema->getModelByClassName($class);
        if (!$modelType) {
            return;
        }

        foreach ($interfaceStack as $ancestorInterface) {
            $modelType->addInterface($ancestorInterface->getName());
        }
        // Models with no exposed subclasses don't get interfaces. There's no
        // value since it can never be reused.
        if (self::isLeafModel($class, $schema)) {
            return;
        }

        $modelStack[] = $modelType;
        $interface = ModelInterfaceType::create(
            $modelType->getModel(),
            static::interfaceName($modelType->getName())
        );
        $interface->setTypeResolver([static::class, 'resolveType']);

        // Start by adding all the fields in the model
        foreach ($modelType->getFields() as $fieldObj) {
            $interface->addField($fieldObj->getName(), $fieldObj->getType());
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
            $modelType->addInterface($interface->getName());
        }

        foreach (InheritanceChain::create($class)->getDirectDescendants() as $class) {
            self::createInterfaces($schema, $class, $modelStack, $interfaceStack);
        }
    }

    /**
     * @param Schema $schema
     * @param string[] $baseModels
     * @return void
     */
    private static function applyBaseInterface(
        Schema $schema,
        array $baseModels
    ): void
    {
        $allTypes = self::getDataObjectTypes($schema);
        $allFields = [];
        foreach ($baseModels as $class) {
            $fields = [];
            $modelType = $schema->getModelByClassName($class);
            foreach ($modelType->getFields() as $fieldObj) {
                $fields[$fieldObj->getName()] = $fieldObj->getType();
            }
            $allFields[] = $fields;
        }
        if (count($allFields) < 2) {
            return;
        }
        $compare = array_shift($allFields);
        $commonFields = array_intersect_assoc($compare, ...$allFields);
        if (empty($commonFields)) {
            return;
        }

        $baseInterface = InterfaceType::create('DataObjectInterface');
        $baseInterface->applyConfig(['fields' => $commonFields]);
        $baseInterface->setDescription('The common interface shared by all DataObject types');
        $baseInterface->setTypeResolver([static::class, 'resolveType']);
        $schema->addInterface($baseInterface);
        foreach ($allTypes as $modelType) {
            $modelType->addInterface($baseInterface->getName());
        }

        // All the fields that were found to be common to the base interface
        // should be removed from all the base models, as they've been promoted to god level
        foreach ($baseModels as $class) {
            $modelType = $schema->getModelByClassName($class);
            $interface = $schema->getInterface(static::interfaceName($modelType->getName()));
            if ($interface) {
                foreach ($baseInterface->getFields() as $fieldObj) {
                    $interface->removeField($fieldObj->getName());
                }
            }
        }
    }

    /**
     * @param Schema $schema
     */
    private static function createUnions(Schema $schema)
    {
        $graph = [];
        foreach (self::getDataObjectTypes($schema) as $modelType) {
            foreach ($modelType->getInterfaces() as $interfaceName) {
                $interface = $schema->getInterface($interfaceName);
                if (!$interface instanceof ModelInterfaceType) {
                    continue;
                }
                if (!isset($graph[$interface->getName()])) {
                    $graph[$interface->getName()] = [];
                }
                $graph[$interface->getName()][] = $modelType->getName();
            }
        }
        foreach ($graph as $interfaceName => $implementations) {
            /* @var ModelInterfaceType $modelInterface */
            $modelInterface = $schema->getInterface($interfaceName);
            $modelName = $modelInterface->getModel()->getTypeName();
            $modelType = $schema->getModel($modelName);
            $name = static::unionName($modelType->getName());
            $union = ModelUnionType::create($modelInterface, $name);
            //$implementations[] = $modelName;
            $union->setTypes($implementations);
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
        /* @var ModelQuery $query */
        foreach ($queries as $query) {
            $typeName = $query->getNamedType();
            $modelType = $schema->getModel($typeName);
            // Type was customised. Ignore.
            if (!$modelType) {
                continue;
            }
            $unionName = static::unionName($modelType->getName());
            if ($union = $schema->getUnion($unionName)) {
                $currentType = $query->getType();
                // [MyType!]! becomes [MyNewType!]!
                $newType = preg_replace('/[A-Za-z_]+/', $unionName, $currentType    );
                $query->setType($newType);
            }
        }
    }

    /**
     * A "base model" is one that either has no ancestors or is one that has no ancestors
     * that are queryable.
     *
     * @param string $class
     * @param Schema $schema
     * @return bool
     */
    private static function isBaseModel(string $class, Schema $schema): bool
    {
        // If this is the base class, and it's readable, it's a base model
        $chain = InheritanceChain::create($class, $schema);
        if ($chain->getBaseClass() === $class) {
            return self::isReadable($schema, $class);
        }

        // This is a subclass. If any ancestors are readable, it's not a base model.
        $ancestors = $chain->getAncestralModels();
        foreach ($ancestors as $ancestor) {
            if (self::isReadable($schema, $ancestor)) {
                return false;
            }
        }
        // Subclass with no readable ancestors. If it's readable, it's the base model.
        return self::isReadable($schema, $class);
    }

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
        // If the class is the lowest level of the tree that's exposed, collect it as a leaf node.
        $registeredDescendants = array_filter($chain->getDescendantModels(), function ($childClass) use ($schema) {
            return $schema->getModelByClassName($childClass) !== null;
        });

        return empty($registeredDescendants);
    }
}
