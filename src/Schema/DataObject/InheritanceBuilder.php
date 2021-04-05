<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use ReflectionException;

/**
 * A schema-aware service for DataObject model types that builds out their
 * inheritance chain in an ORM-like way, applying inherited fields and implicitly
 * exposing ancestral types, etc.
 */
class InheritanceBuilder
{
    use Injectable;

    /**
     * @var Schema
     */
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param ModelType $modelType
     * @throws SchemaBuilderException
     */
    public function fillAncestry(ModelType $modelType): void
    {
        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass());
        $ancestors = $chain->getAncestralModels();
        if (empty($ancestors)) {
            return;
        }
        $parent = $ancestors[0];
        $parentModel = $this->getSchema()->findOrMakeModel($parent);
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
        $this->fillAncestry($parentModel);
    }

    /**
     * @param ModelType $modelType
     * @return void
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    public function fillDescendants(ModelType $modelType): void
    {
        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass());
        $descendants = $chain->getDirectDescendants();
        if (empty($descendants)) {
            return;
        }
        foreach ($descendants as $descendant) {
            $descendantModel = $this->getSchema()->getModelByClassName($descendant);
            if ($descendantModel) {
                foreach ($modelType->getFields() as $fieldObj) {
                    if ($descendantModel->getFieldByName($fieldObj->getName())) {
                        continue;
                    }
                    $clone = clone $fieldObj;
                    $descendantModel->addField($fieldObj->getName(), $clone);
                }
                $this->fillDescendants($descendantModel);
            }
        }
    }

    /**
     * @param string $class
     * @return bool
     */
    public function isBaseModel(string $class): bool
    {
        $chain = InheritanceChain::create($class);
        if ($chain->getBaseClass() === $class) {
            return true;
        }
        foreach ($chain->getAncestralModels() as $class) {
            if ($this->getSchema()->getModelByClassName($class)) {
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
    public function isLeafModel(string $class): bool
    {
        $chain = InheritanceChain::create($class);
        foreach ($chain->getDescendantModels() as $class) {
            if ($this->getSchema()->getModelByClassName($class)) {
                return false;
            }
        }
        return true;
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
     * @return InheritanceBuilder
     */
    public function setSchema(Schema $schema): InheritanceBuilder
    {
        $this->schema = $schema;
        return $this;
    }


}
