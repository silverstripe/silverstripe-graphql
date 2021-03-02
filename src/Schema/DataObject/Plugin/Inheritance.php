<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

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
        $dataobjectTypes = array_filter($schema->getModels(), function (ModelType $modelType) {
            $class = $modelType->getModel()->getSourceClass();
            return is_subclass_of($class, DataObject::class);
        });
        foreach ($dataobjectTypes as $modelType) {
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
        $baseInterface = self::createBaseInterface($schema, $baseModels);
        if ($baseInterface) {
            $schema->addInterface($baseInterface);
            foreach ($dataobjectTypes as $modelType) {
                $modelType->addInterface($baseInterface->getName());
            }
        }

        self::createUnions($schema, $dataobjectTypes);
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
        $schemaContext = SchemaContextProvider::get($context);

        while (!$schemaContext->hasModel($class)) {
            if ($class === DataObject::class) {
                throw new Exception(sprintf(
                    'No models were registered in the ancestry of %s',
                    get_class($obj)
                ));
            }
            $class = get_parent_class($class);
        }
        return $schemaContext->getTypeNameForClass($class);
    }

    /**
     * @param ModelType $model
     * @return string
     */
    public static function modelToInterface(ModelType $model): string
    {
        return $model->getName() . 'Interface';
    }

    /**
     * @param ModelType $model
     * @return string
     */
    public static function modelToUnion(ModelType $model): string
    {
        return $model->getName() . 'InheritanceUnion';
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
            $fieldName = $fieldObj instanceof ModelField ? $fieldObj->getPropertyName() : $fieldObj->getName();
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
        if (self::isLeafModel($class, $schema)) {
            return;
        }

        $modelStack[] = $modelType;
        $interface = ModelInterfaceType::create($modelType->getModel(), static::modelToInterface($modelType));
        $interface->setTypeResolver([static::class, 'resolveType']);
        foreach ($modelType->getFields() as $fieldObj) {
            $interface->addField($fieldObj->getName(), $fieldObj->getType());
        }
        foreach ($interfaceStack as $ancestorInterface) {
            foreach ($ancestorInterface->getFields() as $fieldObj) {
                $interface->removeField($fieldObj->getName());
            }
        }
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
     * @return InterfaceType|null
     */
    private static function createBaseInterface(Schema $schema, array $baseModels): ?InterfaceType
    {
        $allFields = [];
        foreach ($baseModels as $class) {
            $fields = [];
            $modelType = $schema->getModelByClassName($class);
            foreach ($modelType->getFields() as $fieldObj) {
                $fields[$fieldObj->getName()] = $fieldObj->getType();
            }
            $allFields[] = $fields;
        }
        $commonFields = array_intersect_assoc(...$allFields);
        if (empty($commonFields)) {
            return null;
        }

        $interface = InterfaceType::create('DataObjectInterface');
        $interface->applyConfig(['fields' => $commonFields]);
        $interface->setDescription('The common interface shared by all DataObject types');
        $interface->setTypeResolver([static::class, 'resolveType']);

        return $interface;
    }

    /**
     * @param Schema $schema
     * @param ModelType[] $types
     */
    private static function createUnions(Schema $schema, array $types)
    {
        $graph = [];
        foreach ($types as $modelType) {
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
            $name = static::modelToUnion($modelType);
            $union = ModelUnionType::create($modelInterface, $name);
            $implementations[] = $modelName;
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
            $unionName = static::modelToUnion($modelType);
            if ($union = $schema->getUnion($unionName)) {
                $currentType = $query->getName();
                // [MyType!]! becomes [MyNewType!]!
                $newType = preg_replace('/[A-Za-z_]+/', $unionName, $currentType);
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
        $chain = InheritanceChain::create($class, $schema);
        if ($chain->getBaseClass() === $class) {
            return true;
        }

        // Check if any ancestors are queryable.
        $ancestors = $chain->getAncestralModels();
        $hasReadableAncestor = false;
        foreach ($ancestors as $ancestor) {
            $existing = $schema->getModelByClassName($ancestor);
            if ($existing) {
                foreach ($existing->getOperations() as $operation) {
                    if ($operation instanceof ModelQuery) {
                        $hasReadableAncestor = true;
                        break 2;
                    }
                }
            }
        }

        return !$hasReadableAncestor;
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
