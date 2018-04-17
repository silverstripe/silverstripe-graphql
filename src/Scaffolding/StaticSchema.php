<?php

namespace SilverStripe\GraphQL\Scaffolding;

use InvalidArgumentException;
use SilverStripe\Core\ClassInfo;
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
    /**
     * @var StaticSchema
     */
    private static $instance;

    /**
     * @var array
     */
    protected $typesMap;

    /**
     * @var string
     */
    protected $ancestryTypeSuffix = 'WithDescendants';

    /**
     * @return static
     */
    public static function inst()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
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
     * @param $class
     * @return string
     */
    public function typeNameForAncestry($class)
    {
        return $this->typeNameForDataObject($class) . $this->ancestryTypeSuffix;
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
     * Gets the type name for a union type of all ancestors of a class given the type name
     * @param string $typeName
     * @return string|null
     */
    public function ancestryTypeName($typeName)
    {
        return $typeName . $this->ancestryTypeSuffix;
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
        return ($instance->hasMethod($fieldName) || $instance->hasField($fieldName));
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
     * @param $dataObjectClass
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
     * @param $dataObjectClass
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

        return $descendants;
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
     * @param $class
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
