<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Core\Convert;
use SilverStripe\GraphQL\QueryHandler\SchemaContextProvider;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceChain;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\ModelUnionType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\ORM\DataObject;
use ReflectionException;

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
            self::createInterfaces($schema, $class);
        }
        self::createUnions($schema, $dataobjectTypes);
    }

    /**
     * @param $obj
     * @param $context
     * @return string
     * @throws SchemaBuilderException
     */
    public static function resolveUnion($obj, $context): string
    {
        $schemaContext = SchemaContextProvider::get($context);
        return $schemaContext->getTypeNameForClass(get_class($obj));
    }

    /**
     * @param Schema $schema
     * @param string $leafClass
     * @throws SchemaBuilderException
     */
    private static function fillAncestry(Schema $schema, string $leafClass): void
    {
        $chain = InheritanceChain::create($leafClass);
        $leafModel = $schema->getModelByClassName($leafClass);
        foreach ($chain->getAncestralModels() as $class) {
            $parentModel = $schema->findOrMakeModel($class);
            // Merge descendant fields up into the ancestor
            foreach ($leafModel->getFields() as $fieldObj) {
                // If the field already exists on the ancestor, skip it
                if ($parentModel->getFieldByName($fieldObj->getName())) {
                    continue;
                }
                $fieldName = $fieldObj instanceof ModelField ? $fieldObj->getPropertyName() : $fieldObj->getName();
                // If the field is unique to the descendant, skip it.
                if ($parentModel->getModel()->hasField($fieldName)) {
                    $clone = clone $fieldObj;
                    $parentModel->addField($clone);
                }
            }
        }
    }

    /**
     * @param Schema $schema
     * @param string $class
     * @param ModelType[] $stack
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    private static function createInterfaces(Schema $schema, string $class, array $stack = [])
    {
        $ancestorInterfaces = array_map(function (ModelType $model) use ($schema) {
            $interfaceName = self::modelToInterface($model);
            $ancestorInterface = $schema->getInterface($interfaceName);
            Schema::invariant(
                $ancestorInterface,
                'Could not find ancestor interface %s for model %s',
                $interfaceName,
                $model->getName()
            );
            return $ancestorInterface;
        }, $stack);

        $modelType = $schema->getModelByClassName($class);
        foreach ($ancestorInterfaces as $ancestor) {
            $modelType->addInterface($ancestor);
        }
        if (self::isLeafModel($class, $schema)) {
            return;
        }

        $stack[] = $modelType;
        $interface = ModelInterfaceType::create($modelType->getModel(), self::modelToInterface($modelType));
        foreach ($modelType->getFields() as $fieldObj) {
            $interface->addField($fieldObj->getName(), $fieldObj->getType());
        }
        foreach ($ancestorInterfaces as $ancestor) {
            foreach ($ancestor->getFields() as $fieldObj) {
                $interface->removeField($fieldObj->getName());
            }
        }
        $schema->addInterface($interface);
        $modelType->addInterface($interface);

        self::createInterfaces($schema, $class, $stack);
    }

    /**
     * @param Schema $schema
     * @param ModelType[] $types
     */
    private static function createUnions(Schema $schema, array $types)
    {
        $graph = [];
        foreach ($types as $modelType) {
            foreach ($modelType->getInterfaces() as $interface) {
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
            if (count($implementations) < 2) {
                continue;
            }
            $union = ModelUnionType::create(
                $schema->getInterface($interfaceName),
                $interfaceName . 'InheritanceUnion'
            );
            $union->setTypes($implementations);
            $union->setTypeResolver([static::class, 'resolveUnion']);
            $schema->addUnion($union);
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

    /**
     * @param ModelType $model
     * @return string
     */
    private static function modelToInterface(ModelType $model): string
    {
        return $model->getName() . 'Interface';
    }
}
