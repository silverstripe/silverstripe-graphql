<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use SilverStripe\Core\Convert;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceChain;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
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
        foreach ($schema->getModels() as $modelType) {
            $class = $modelType->getModel()->getSourceClass();
            if (!is_subclass_of($class, DataObject::class)) {
                continue;
            }
            $baseClass = InheritanceChain::create($class)->getBaseClass();
            if (self::isTouched($baseClass)) {
                continue;
            }
            self::addInheritance($schema, $baseClass);
            self::touchNode($baseClass);
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
        }

        if (!$inheritance->hasDescendants()) {
            return;
        }

        // Add the new __extends field to the base class only
        if (!$parentModel) {
            $result = $inheritance->getExtensionType();
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
                    $nativeFields = $model->getUninheritedFields();
                    if (empty($nativeFields)) {
                        continue;
                    }

                    /* @var ModelField $fieldObj */
                    foreach ($existingType->getFields() as $fieldObj) {
                        // Add the field if it's explicitly added and native
                        $isNative = in_array($fieldObj->getName(), $nativeFields);
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
     * @param string $baseClass
     */
    private static function touchNode(string $baseClass): void
    {
        self::$touchedNodes[$baseClass] = true;
    }

    /**
     * @param string $baseClass
     * @return bool
     */
    private static function isTouched(string $baseClass): bool
    {
        return isset(self::$touchedNodes[$baseClass]);
    }

}
