<?php

namespace SilverStripe\GraphQL\Scaffolding;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use Exception;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\FieldAccessorInterface;
use SilverStripe\GraphQL\Manager;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;

/**
 * Global map of dataobject classes to graphql schema types.
 * Provides an automatic scaffold mechanism for any classes
 * without explicit mapping.
 *
 * This must be done globally and prior to scaffolding as type mapping
 * must be determined before scaffolding begins.
 */
class StaticSchema
{
    use Configurable;

    const PREFER_UNION = 1;

    const PREFER_SINGLE = 2;

    /**
     * @var StaticSchema
     */
    private static $instance;

    /**
     * @var array
     */
    protected $typesMap;

    /**
     * @var FieldAccessorInterface|null
     */
    protected $fieldAccessor;

    /**
     * @var callable
     */
    protected $fieldFormatter;

    /**
     * @config
     * @var string
     */
    private static $inheritanceTypeSuffix = 'WithDescendants';

    /**
     * @return static
     */
    public static function inst()
    {
        if (!static::$instance) {
            static::setInstance(new static());
        }

        return static::$instance;
    }

    /**
     * @param StaticSchema $inst
     */
    public static function setInstance(StaticSchema $inst = null)
    {
        static::$instance = $inst;
    }

    /**
     * Removes the current instance
     */
    public static function reset()
    {
        static::setInstance();
    }

    /**
     * Boots the type names from config. No graphql request necessary.
     * @param string $schemaName
     * @return $this
     */
    public function load(string $schemaName): self
    {
        $schemaConfig = Manager::config()->get('schemas');
        $typeNames = $schemaConfig[$schemaName]['typeNames'] ?? [];

        return $this->setTypeNames($typeNames);
    }

    /**
     * Given a DataObject subclass name, transform it into a sanitised (and implicitly unique) type
     * name suitable for the GraphQL schema
     *
     * @param string $class
     * @return string
     */
    public function typeNameForDataObject($class)
    {
        $customTypeName = $this->mappedTypeName($class);
        if ($customTypeName) {
            return $customTypeName;
        }

        $parts = explode('\\', $class);
        $typeName = sizeof($parts) > 1 ? $parts[0] . end($parts) : $parts[0];

        return $this->typeName($typeName);
    }

    /**
     * Gets the type name for a union type of all ancestors of a class given the classname
     * @param string $class
     * @return string
     */
    public function inheritanceTypeNameForDataObject($class)
    {
        $typeName = $this->typeNameForDataObject($class);
        return $this->inheritanceTypeNameForType($typeName);
    }

    /**
     * Gets the type name for a union type of all ancestors of a class given the type name
     * @param string $typeName
     * @return string
     */
    public function inheritanceTypeNameForType($typeName)
    {
        return $typeName . $this->config()->get('inheritanceTypeSuffix');
    }

    /**
     * @param string $str
     * @return mixed
     */
    public function typeName($str)
    {
        return preg_replace('/[^A-Za-z0-9_]/', '_', str_replace(' ', '', $str));
    }

    /**
     * Returns true if the field name can be accessed on the given object
     *
     * @param ViewableData $instance
     * @param string $fieldName
     * @return bool
     */
    public function isValidFieldName(ViewableData $instance, $fieldName)
    {
        if ($instance->hasMethod($fieldName) || $instance->hasField($fieldName)) {
            return true;
        }
        if ($this->getFieldAccessor()) {
            return $this->getFieldAccessor()->getObjectFieldName($instance, $fieldName) !== null;
        }

        return false;
    }

    /**
     * @param array $typesMap An associate array of classname => type name
     * @return $this
     */
    public function setTypeNames($typesMap)
    {
        if ($typesMap && !ArrayLib::is_associative($typesMap)) {
            throw new InvalidArgumentException(sprintf(
                '%s.typeNames must be a map of class names to type names',
                static::class
            ));
        }
        $allTypes = array_values($typesMap);
        $diff = array_unique(
            array_diff_assoc(
                $allTypes,
                array_unique($allTypes)
            )
        );

        if (!empty($diff)) {
            throw new InvalidArgumentException(sprintf(
                '%s.typeNames contains duplicate type names: %s',
                static::class,
                implode(', ', $diff)
            ));
        }

        foreach ($typesMap as $class => $type) {
            $this->ensureDataObject($class);
        }

        $this->typesMap = $typesMap;

        return $this;
    }

    /**
     * Gets all ancestors of a DataObject
     * @param string $dataObjectClass
     * @return array
     */
    public function getAncestry($dataObjectClass)
    {
        $classes = [];
        $ancestry = array_reverse(ClassInfo::ancestry($dataObjectClass));

        foreach ($ancestry as $class) {
            if ($class === $dataObjectClass) {
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
     * @param string $dataObjectClass
     * @return array
     * @throws InvalidArgumentException
     */
    public function getDescendants($dataObjectClass)
    {
        if (!is_subclass_of($dataObjectClass, DataObject::class)) {
            throw new InvalidArgumentException(sprintf(
                '%s::getDescendants takes only %s subclasses',
                __CLASS__,
                DataObject::class
            ));
        }

        $descendants = ClassInfo::subclassesFor($dataObjectClass);
        array_shift($descendants);

        return array_values($descendants);
    }

    /**
     * Gets the type from the manager given a DataObject class. Will use an
     * inheritance type if available.
     * @param string $class
     * @param Manager $manager
     * @param int $mode
     * @return Type
     */
    public function fetchFromManager($class, Manager $manager, $mode = self::PREFER_UNION)
    {
        if (!in_array($mode, [self::PREFER_UNION, self::PREFER_SINGLE])) {
            throw new InvalidArgumentException(sprintf(
                '%s::%s illegal mode %s. Allowed modes are PREFER_UNION, PREFER_SINGLE',
                __CLASS__,
                __FUNCTION__,
                $mode
            ));
        }
        $typeName = $this->typeNameForDataObject($class);
        $inheritanceTypeName = $this->inheritanceTypeNameForDataObject($class);
        $names = $mode === self::PREFER_UNION
            ? [$inheritanceTypeName, $typeName]
            : [$typeName, $inheritanceTypeName];

        foreach ($names as $type) {
            if ($manager->hasType($type)) {
                return $manager->getType($type);
            }
        }

        throw new InvalidArgumentException(sprintf(
            'The class %s could not be resolved to any type in the manager instance.',
            $class
        ));
    }

    /**
     * @param Manager $manager
     * @return array
     * @throws Exception
     */
    public function introspectTypes(Manager $manager)
    {
        $fragments = $manager->query(
            <<<GRAPHQL
query IntrospectionQuery {
    __schema {
      types {
        kind
        name
        possibleTypes {
          name
        }
      }
    }
}
GRAPHQL
        );

        if (isset($fragments['errors'])) {
            $messages = array_map(function ($error) {
                return $error['message'];
            }, $fragments['errors']);

            throw new Exception(sprintf(
                'There were some errors with the introspection query: %s',
                implode(PHP_EOL, $messages)
            ));
        }

        return $fragments;
    }

    /**
     * @param $field
     * @return string
     */
    public function formatField(string $field): string
    {
        if ($this->getFieldFormatter()) {
            return call_user_func_array($this->getFieldFormatter(), [$field]);
        }

        return $field;
    }

    /**
     * @internal
     * @param array $fields
     * @return array
     */
    public function formatFields(array $fields): array
    {
        if (!$this->getFieldFormatter()) {
            return $fields;
        }

        return array_map(function ($field) {
            return $this->formatField($field);
        }, $fields);
    }

    /**
     * Formats all the keys of an array, preserving the values
     * @param array $arr
     * @return array
     */
    public function formatKeys(array $arr): array
    {
        if (!$this->getFieldFormatter()) {
            return $arr;
        }
        if (!ArrayLib::is_associative($arr)) {
            return $arr;
        }

        $newArr = [];
        foreach ($arr as $k => $v) {
            $newArr[$this->formatField($k)] = $v;
        }

        return $newArr;
    }

    /**
     * Extracts the values for a list of keys
     * @param array $keys
     * @param array $arr
     * @param bool $graceful
     * @return array
     */
    public function extractKeys(array $keys, array $arr, $graceful = true): array
    {
        return array_map(function ($key) use ($graceful, $arr) {
            $formatted = $this->formatField($key);
            return $graceful ? ($arr[$formatted] ?? null) : $arr[$formatted];
        }, $keys);
    }

    /**
     * @return FieldAccessorInterface|null
     */
    public function getFieldAccessor()
    {
        return $this->fieldAccessor;
    }

    /**
     * @param ViewableData $obj
     * @param $fieldName
     * @return mixed
     */
    public function accessField(ViewableData $obj, $fieldName)
    {
        return $this->getFieldAccessor()
            ? $this->getFieldAccessor()->getValue($obj, $fieldName, [], true)
            : $obj->obj($fieldName);
    }

    /**
     * @internal
     * @param FieldAccessorInterface|null $fieldAccessor
     * @return $this
     */
    public function setFieldAccessor($fieldAccessor)
    {
        $this->fieldAccessor = $fieldAccessor;

        return $this;
    }

    /**
     * @return callable
     */
    public function getFieldFormatter()
    {
        return $this->fieldFormatter;
    }

    /**
     * @internal
     * @param callable $fieldFormatter
     * @return $this
     */
    public function setFieldFormatter($fieldFormatter)
    {
        if (!is_callable($fieldFormatter)) {
            throw new InvalidArgumentException('Field formatter must be callable');
        }
        $this->fieldFormatter = $fieldFormatter;

        return $this;
    }


    /**
     * @param string $class
     * @return mixed|null
     */
    protected function mappedTypeName($class)
    {
        return isset($this->typesMap[$class]) ? $this->typesMap[$class] : null;
    }

    /**
     * @param string $class
     * @throws InvalidArgumentException
     */
    protected function ensureDataObject($class)
    {
        if (!is_subclass_of($class, DataObject::class)) {
            throw new InvalidArgumentException(sprintf(
                '%s is not a subclass of %s',
                $class,
                DataObject::class
            ));
        }
    }
}
