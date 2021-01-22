<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Core\Convert;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceChain;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
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
     * @var array
     */
    private static $touchedNodes = [];

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
        foreach ($schema->getModels() as $modelType) {
            $class = $modelType->getModel()->getSourceClass();
            if (!is_subclass_of($class, DataObject::class)) {
                continue;
            }
            if (self::isBaseModel($class, $schema)) {
                $baseModels[] = $class;
            }
        }

        foreach ($baseModels as $baseClass) {
            if (self::isTouched($schema, $baseClass)) {
                continue;
            }
            self::addInheritance($schema, $baseClass);
            self::touchNode($schema, $baseClass);
        }
    }

    /**
     * @param Schema $schema
     * @param string $class
     * @param ModelType|null $parentModel
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    private static function addInheritance(Schema $schema, string $class, ?ModelType $parentModel = null)
    {
        $inheritance = InheritanceChain::create($class);
        $modelType = $schema->getModelByClassName($class);
        if (!$modelType) {
            $modelType = $schema->findOrMakeModel($class);
        }
        // Merge with the parent model for inherited fields
        if ($parentModel) {
            $modelType->mergeWith($parentModel);
            //$modelType->removeField(InheritanceChain::getName());
        }

        if (!$inheritance->hasDescendants()) {
            return;
        }

        // Add the new _extend field to the base class only
        if (!$parentModel) {
            $result = $inheritance->getExtensionType($schema->getSchemaContext());
            if ($result) {
                /* @var Type $extendsType */
                list($extendsType, $subtypes) = $result;
                $extendFields = [];
                foreach ($subtypes as $modelName => $subtype) {
                    $existingType = $schema->getModel($modelName);

                    // If the type has not been explicitly added, skip over it, because there's nothing
                    // to show in _extend (other than id, which we already have)
                    if (!$existingType) {
                        continue;
                    }

                    /* @var DataObjectModel $model */
                    $model = $subtype->getModel();

                    // If the type is exposed, but has no native fields, skip over it. Nothing to show.
                    $nativeFields = array_map('strtolower', $model->getUninheritedFields());
                    if (empty($nativeFields)) {
                        continue;
                    }

                    /* @var ModelField $fieldObj */
                    foreach ($existingType->getFields() as $fieldObj) {
                        // Add the field if it's explicitly added and native
                        $isNative = in_array(strtolower($fieldObj->getName()), $nativeFields);
                        // If it's a custom property, e.g. Comments.Count(), throw it in, too
                        $isCustom = $fieldObj->getProperty() !== null;

                        if ($isNative || $isCustom) {
                            $subtype->addField($fieldObj->getName(), $fieldObj);
                        }
                    }

                    // Remove default fields, like "id"
                    foreach ($model->getDefaultFields() as $fieldName => $propName) {
                        $subtype->removeField($fieldName);
                    }

                    if (!empty($subtype->getFields())) {
                        $extendFieldName = Convert::upperCamelToLowerCamel($modelName);
                        $extendFields[$extendFieldName] = $subtype->getName();
                        $schema->addModel($subtype);
                    }
                }
                if (!empty($extendFields)) {
                    $extendsType->setFields($extendFields);
                    $schema->addType($extendsType);
                    $modelType->addField(InheritanceChain::getName(), [
                        'type' => $extendsType->getName(),
                        'resolver' => [InheritanceChain::class, 'resolveExtensionType']
                    ]);
                }
            }
        }
        foreach ($inheritance->getDirectDescendants() as $descendantClass) {
            self::addInheritance($schema, $descendantClass, $modelType);
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
     * @param Schema $schema
     * @param string $baseClass
     */
    private static function touchNode(Schema $schema, string $baseClass): void
    {
        $key = md5($schema->getSchemaKey() . $baseClass);
        self::$touchedNodes[$key] = true;
    }

    /**
     * @param Schema $schema
     * @param string $baseClass
     * @return bool
     */
    private static function isTouched(Schema $schema, string $baseClass): bool
    {
        $key = md5($schema->getSchemaKey() . $baseClass);
        return isset(self::$touchedNodes[$key]);
    }
}
