<?php


namespace SilverStripe\GraphQL\Schema\Storage;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use Exception;

abstract class AbstractTypeRegistry
{
    protected static $types = [];

    /**
     * @param string $typename
     * @return mixed|null
     * @throws Exception
     */
    public static function get(string $typename)
    {
        return static::fromCache($typename);
    }

    abstract protected static function getSourceDirectory(): string;

    abstract protected static function getSourceNamespace(): string;

    /**
     * @param string $typename
     * @return mixed|null
     * @throws Exception
     */
    protected static function fromCache(string $typename)
    {
        $type = null;
        if (!isset(static::$types[$typename])) {
            $file = static::getSourceDirectory() . DIRECTORY_SEPARATOR . $typename . '.php';
            if (file_exists($file)) {
                require_once($file);
                $cls = static::getSourceNamespace() . '\\' . $typename;
                if (class_exists($cls)) {
                    $type = new $cls();
                }
            }
            static::$types[$typename] = $type;
        }
        $type = static::$types[$typename];
        if (!$type) {
            throw new Exception("Unknown graphql type: " . $typename);
        }
        return $type;
    }

    public static function ID(): ScalarType
    {
        return Type::id();
    }

    public static function String(): ScalarType
    {
        return Type::string();
    }

    public static function Boolean(): ScalarType
    {
        return Type::boolean();
    }

    public static function Float(): ScalarType
    {
        return Type::float();
    }

    public static function Int(): ScalarType
    {
        return Type::int();
    }

    public static function listOf($type): ListOfType
    {
        return new ListOfType($type);
    }

    public static function nonNull($type): NonNull
    {
        return new NonNull($type);
    }
}
