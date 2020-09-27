<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\ORM\DataObject;
use ReflectionException;
use InvalidArgumentException;

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
     * @var string
     * @config
     */
    private static $field_name = '__extend';

    /**
     * @var callable
     * @config
     */
    private static $descendant_typename_creator = [ self::class, 'createDescendantTypename' ];

    /**
     * @var callable
     * @config
     */
    private static $subtype_name_creator = [ self::class, 'createSubtypeName' ];

    /**
     * @var array
     */
    private $descendantTypeResult;

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
     * @return string
     */
    public static function getName(): string
    {
        return static::config()->get('field_name');
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
     * @return string
     */
    public function getBaseClass(): string
    {
        return $this->inst->baseClass();
    }

    /**
     * @return array|null
     * @throws ReflectionException
     * @throws SchemaBuilderException
     */
    public function getExtensionType(): ?array
    {
        if ($this->descendantTypeResult) {
            return $this->descendantTypeResult;
        }
        if (empty($this->getDescendantModels())) {
            return null;
        }
        $typeName = call_user_func_array(
            $this->config()->get('descendant_typename_creator'),
            [$this->inst]
        );

        $nameCreator = $this->config()->get('subtype_name_creator');

        $fields = [];
        $subtypes = [];
        foreach ($this->getDescendantModels() as $className) {
            $modelType = ModelType::create($className);
            /* @var DataObjectModel $model */
            $model = $modelType->getModel();

            // Extension types only show their own fields
            $nativeFields = $model->getUninheritedFields();
            if (empty($nativeFields)) {
                continue;
            }
            foreach ($nativeFields as $fieldName) {
                $modelType->addField($fieldName);
            }
            $originalName = $modelType->getName();
            $newName = call_user_func_array($nameCreator, [$originalName]);
            $modelType->setName($newName);
            $subtypes[] = $modelType;
            $fieldName = Convert::upperCamelToLowerCamel($originalName);
            $fields[$fieldName] = $newName;
        }

        if (empty($fields)) {
            return null;
        }

        $descendantType = Type::create($typeName, [
            'fields' => $fields,
            'fieldResolver' => [static::class, 'resolveExtensionType'],
        ]);

        $this->descendantTypeResult = [$descendantType, $subtypes];

        return $this->descendantTypeResult;
    }

    /**
     * @param DataObject $dataObject
     * @return string
     * @throws SchemaBuilderException
     */
    public static function createDescendantTypename(DataObject $dataObject): string
    {
        return DataObjectModel::create($dataObject)->getTypeName() . 'Descendants';
    }

    /**
     * @param string $modelTypeName
     * @return string
     */
    public static function createSubtypeName(string $modelTypeName): string
    {
        return $modelTypeName . 'ExtensionType';
    }

    /**
     * Noop, because __extends is just structure
     * @param $obj
     * @return DataObject|null
     */
    public static function resolveExtensionType($obj): ?DataObject
    {
        return $obj;
    }

}
