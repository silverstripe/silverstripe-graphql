<?php


namespace SilverStripe\GraphQL\Schema\Storage;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use Exception;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Controller as GraphQLController;

abstract class AbstractTypeRegistry
{
    use Configurable;

    /**
     * Whether to rebuild the schema if there is a mysteriously missing schema file.
     * This is a fallback in addition to the regular fallback of building the schema
     * if there is no schema, if autobuildSchema is enabled.
     * This fallback is for edge case where a __graphql-schema.php file exists, though
     * the schema files themselves are missing.
     */
    private static bool $rebuild_on_missing_schema_file = true;
    
    /**
     * Minimum number of seconds between rebuilding the schema if a schema file is missing.
     * If set too low this may make the site vulnerable to the schema being repeatedly rebuilt
     * via specially crafted graphql requests
     */
    private static int $rebuild_on_missing_schema_file_minimum_interval = 900;

    protected static $types = [];

    /**
     * @param string $typename
     * @return mixed|null
     * @throws Exception
     */
    public static function get(string $typename)
    {
        try {
            return static::fromCache($typename);
        } catch (Exception $e) {
            if (preg_match('/(Missing|Unknown) graphql/', $e->getMessage()) || !self::canRebuildOnMissing()) {
                throw $e;
            }
            // Try to rebuild the whole schema as fallback.
            // This is to solve mysterious edge cases where schema files do not exist when they should.
            // These edge cases are more likely on multi-server environments
            $dirParts = explode(DIRECTORY_SEPARATOR, static::getSourceDirectory());
            $key = $dirParts[count($dirParts) - 1];
            $builder = SchemaBuilder::singleton();
            $schema = $builder->boot($key);
            try {
                $builder->build($schema, true);
                $path = self::getRebuildOnMissingPath();
                file_put_contents($path, time());
            } catch (EmptySchemaException $e) {
                // noop
            }
            // Attempt to return again now the schema has been rebuilt.
            return static::fromCache($typename);
        }
    }

    private static function canRebuildOnMissing(): bool
    {
        if (!Controller::has_curr() ||
            !(Controller::curr() instanceof GraphQLController) ||
            !Controller::curr()->autobuildEnabled()
        ) {
            return false;
        }
        if (!self::config()->get('rebuild_on_missing_schema_file')) {
            return false;
        }
        $path = self::getRebuildOnMissingPath();
        if (!(is_writable(dirname($path)))) {
            return false;
        }
        if (file_exists($path)) {
            $lastRebuildTimestamp = (int) trim(file_get_contents($path));
            $minInterval = self::config()->get('rebuild_on_missing_schema_file_minimum_interval');
            if ((time() - $lastRebuildTimestamp) < $minInterval) {
                return false;
            }
        }
        return true;
    }

    private static function getRebuildOnMissingPath(): string
    {
        return static::getSourceDirectory() . DIRECTORY_SEPARATOR . self::getRebuildOnMissingFilename();
    }

    private static function getRebuildOnMissingFilename(): string
    {
        return '__rebuild_on_missing';
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
        /* @var NameObfuscator $obfuscator */
        $obfuscator = Injector::inst()->get(NameObfuscator::class);
        $type = null;
        if (!isset(static::$types[$typename])) {
            $obfuscatedName = $obfuscator->obfuscate($typename);
            $file = static::getSourceDirectory() . DIRECTORY_SEPARATOR . $obfuscatedName . '.php';
            if (!file_exists($file)) {
                throw new Exception('Missing graphql file for ' . $typename);
            }
            require_once($file);
            $cls = static::getSourceNamespace() . '\\' . $obfuscatedName;
            if (!class_exists($cls)) {
                throw new Exception('Missing graphql class for ' . $typename);
            }
            $type = new $cls();
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
