<?php

namespace SilverStripe\GraphQL\Scaffolding;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\View\ViewableData;
use \InvalidArgumentException;

class Schema
{
    use Configurable;

    /**
     * @var Schema
     */
    private static $instance;

    /**
     * @var array
     */
    protected $typesMap;

    /**
     * @return static
     */
    public static function inst()
    {
        return self::$instance ?: new static();
    }

    /**
     * Schema constructor.
     */
    public function __construct()
    {
        $typesMap = $this->config()->get('typeNames') ?: [];
        if (!empty($typesMap)) {
            if (!ArrayLib::is_associative($typesMap)) {
                throw new InvalidArgumentException(sprintf(
                    '%s.typeNames must be a map of class names to type names',
                    static::class
                ));
            }
            $allTypes = array_values($typesMap);
            $uniqueTypes = array_unique($allTypes);
            $diff = array_diff($allTypes, $uniqueTypes);

            if (!empty($diff)) {
                throw new InvalidArgumentException(sprintf(
                    '%s.typeNames contains duplicate type names: %s',
                    static::class,
                    implode(', ', $diff)
                ));
            }
        }
        $this->typesMap = $typesMap;
    }

    /**
     * Given a DataObject subclass name, transform it into a sanitised (and implicitly unique) type
     * name suitable for the GraphQL schema
     *
     * @param  string $class
     * @return string
     */
    public function typeNameForDataObject($class)
    {
        $customTypeName = $this->mappedTypeName($class);
        if ($customTypeName) {
            return $customTypeName;
        }
        $typeName = Config::inst()->get($class, 'table_name', Config::UNINHERITED) ?:
            Injector::inst()->get($class)->singular_name();

        return $this->typeName($typeName);
    }

    /**
     * @param $str
     * @return mixed
     */
    public function typeName($str)
    {
        return preg_replace('/[^A-Za-z0-9_]/', '_', $str);
    }

    /**
     * Returns true if the field name can be accessed on the given object
     *
     * @param  ViewableData $instance
     * @param  $fieldName
     * @return bool
     */
    public function isValidFieldName(ViewableData $instance, $fieldName)
    {
        return ($instance->hasMethod($fieldName) || $instance->hasField($fieldName));
    }

    /**
     * @param $class
     * @return mixed|null
     */
    protected function mappedTypeName($class)
    {
        return isset($this->typesMap[$class]) ? $this->typesMap[$class] : null;
    }
}
