<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\ModelAbstraction;
use SilverStripe\GraphQL\Schema\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\TypeAbstraction;
use SilverStripe\ORM\DataObject;
use ReflectionException;

class InheritanceChain
{
    use Injectable;
    use Configurable;

    /**
     * @var DataObject
     */
    private $dataObject;

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
     * @var TypeAbstraction
     */
    private $descendantType;

    /**
     * InheritanceChain constructor.
     * @param DataObject $dataObject
     */
    public function __construct(DataObject $dataObject)
    {
        $this->dataObject = $dataObject;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return static::config()->get('field_name');
    }

    /**
     * @return ModelAbstraction[]
     */
    public function getAncestralModels(): array
    {
        $classes = [];
        $ancestry = array_reverse(ClassInfo::ancestry($this->dataObject));

        foreach ($ancestry as $class) {
            if ($class === get_class($this->dataObject)) {
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
     * @return ModelAbstraction[]
     * @throws ReflectionException
     */
    public function getDescendantModels(): array
    {
        $descendants = ClassInfo::subclassesFor($this->dataObject, false);

        return array_values($descendants);
    }

    /**
     * @return TypeAbstraction|null
     * @throws ReflectionException
     */
    public function getExtensionType(): ?TypeAbstraction
    {
        if ($this->descendantType) {
            return $this->descendantType;
        }
        if (empty($this->getDescendantModels())) {
            return null;
        }
        $typeName = call_user_func_array(
            $this->config()->get('descendant_typename_creator'),
            [$this->dataObject]
        );
        $fields = [];
        foreach ($this->getDescendantModels() as $className) {
            $abstract = ModelAbstraction::create($className);
            $fieldName = static::typeNameToFieldName($abstract->getName());
            $fields[$fieldName] = $abstract->getName();
        }

        $this->descendantType = TypeAbstraction::create($typeName, [
            'fields' => $fields,
            'fieldResolver' => [static::class, 'resolveExtensionType'],
        ]);

        return $this->descendantType;
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
     * SiteTree -> siteTree, PDOQuery -> pdoQuery
     * @param string $typeName
     * @return string
     */
    public static function typeNameToFieldName(string $typeName): string
    {
        return preg_replace_callback('/^([A-Z]+)/', function ($matches) use ($typeName) {
            $part = strtolower($matches[1]);
            $len = strlen($matches[1]);
            if (strlen($len > 1 && $len < strlen($typeName))) {
                $last = strlen($part) - 1;
                $part[$last] = strtoupper($part[$last]);
            }
            return $part;
        }, $typeName);
    }

    /**
     * @param $obj
     * @return DataObject|null
     */
    public static function resolveExtensionType($obj): ?DataObject
    {
        return $obj;
    }

}
