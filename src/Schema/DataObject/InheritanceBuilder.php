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

    /**
     * @var array
     */
    private $hideAncestors = [];

    /**
     * InheritanceBuilder constructor.
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
     * @throws SchemaBuilderException
     */
    public function fillAncestry(ModelType $modelType): void
    {
        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass())
            ->hideAncestors($this->hideAncestors);
        $ancestors = $chain->getAncestralModels();
        $ancestorModel = null;
        foreach ($ancestors as $ancestor) {
            $ancestorModel = $this->getSchema()->findOrMakeModel($ancestor);
            if ($ancestorModel && $ancestorModel->getName() !== $modelType->getName()) {
                break;
            }
            $ancestorModel = null;
        }
        if (!$ancestorModel) {
            return;
        }
        // Merge descendant fields up into the ancestor
        foreach ($modelType->getFields() as $fieldObj) {
            // If the field already exists on the ancestor with the same config, skip it
            if ($existing = $ancestorModel->getFieldByName($fieldObj->getName())) {
                if ($existing->getSignature() === $fieldObj->getSignature()) {
                    continue;
                }
            }
            $fieldName = $fieldObj instanceof ModelField
                ? $fieldObj->getPropertyName()
                : $fieldObj->getName();
            // If the field is unique to the descendant, skip it.
            if ($ancestorModel->getModel()->hasField($fieldName)) {
                $clone = clone $fieldObj;
                $ancestorModel->addField($fieldObj->getName(), $clone);
            }
        }
        $this->fillAncestry($ancestorModel);
    }

    /**
     * @param ModelType $modelType
     * @return void
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    public function fillDescendants(ModelType $modelType): void
    {
        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass())
            ->hideAncestors($this->hideAncestors);

        $descendants = $chain->getDirectDescendants();
        if (empty($descendants)) {
            return;
        }
        foreach ($descendants as $descendant) {
            $descendantModel = $this->getSchema()->getModelByClassName($descendant);
            if ($descendantModel) {
                foreach ($modelType->getFields() as $fieldObj) {
                    if ($existing = $descendantModel->getFieldByName($fieldObj->getName())) {
                        if ($existing->getSignature() === $fieldObj->getSignature()) {
                            continue;
                        }
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
        if (!$this->getSchema()->getModelByClassName($class)) {
            return false;
        }

        $chain = InheritanceChain::create($class)
            ->hideAncestors($this->hideAncestors);

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
        if (!$this->getSchema()->getModelByClassName($class)) {
            return false;
        }

        $chain = InheritanceChain::create($class)
            ->hideAncestors($this->hideAncestors);

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
