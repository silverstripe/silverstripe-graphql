<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\ORM\DataObject;
use ReflectionException;

/**
 * Utility class that abstracts away class ancestry computations and creates
 * an inheritance "type" for a DataObject
 */
class InheritanceChain
{
    use Injectable;
    use Configurable;

    /**
     * @var string
     */
    private $dataObjectClass;

    /**
     * @var DataObject
     */
    private $inst;

    /**
     * InheritanceChain constructor.
     * @param string $dataObjectClass
     * @throws SchemaBuilderException
     */
    public function __construct(string $dataObjectClass)
    {
        $this->dataObjectClass = $dataObjectClass;
        Schema::invariant(
            is_subclass_of($this->dataObjectClass, DataObject::class),
            '%s only accepts %s subclasses',
            __CLASS__,
            DataObject::class
        );
        $this->inst = DataObject::singleton($this->dataObjectClass);
    }

    /**
     * @return array
     */
    public function getAncestralModels(): array
    {
        $classes = [];
        $ancestry = array_reverse(ClassInfo::ancestry($this->dataObjectClass));

        foreach ($ancestry as $class) {
            if ($class === $this->dataObjectClass) {
                continue;
            }
            if ($class == DataObject::class) {
                break;
            }
            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * @return bool
     */
    public function hasAncestors(): bool
    {
        return count($this->getAncestralModels()) > 0;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getDescendantModels(): array
    {
        $descendants = ClassInfo::subclassesFor($this->dataObjectClass, false);

        return array_values($descendants);
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getDirectDescendants(): array
    {
        $parentClass = $this->dataObjectClass;
        return array_filter($this->getDescendantModels(), function ($class) use ($parentClass) {
            return get_parent_class($class) === $parentClass;
        });
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    public function hasDescendants(): bool
    {
        return count($this->getDescendantModels()) > 0;
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    public function hasInheritance(): bool
    {
        return $this->hasDescendants() || $this->hasAncestors();
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getInheritance(): array
    {
        return array_merge($this->getAncestralModels(), $this->getDescendantModels());
    }

    /**
     * @return string
     */
    public function getBaseClass(): string
    {
        return $this->inst->baseClass();
    }
}
