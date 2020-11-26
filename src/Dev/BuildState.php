<?php


namespace SilverStripe\GraphQL\Dev;


use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;

class BuildState
{
    use Injectable;

    /**
     * @var Schema|null
     */
    private static $activeBuild;


    /**
     * @return Schema|null
     */
    public static function getActiveBuild(): ?Schema
    {
        return self::$activeBuild;
    }

    /**
     * @return Schema
     * @throws SchemaBuilderException
     */
    public static function requireActiveBuild(): Schema
    {
        $schema = static::getActiveBuild();
        Schema::invariant(
            $schema,
            'Attempted to access schema building tools when no build was active'
        );

        return $schema;
    }

    /**
     * @param Schema $schema
     */
    public static function activate(Schema $schema): void
    {
        self::$activeBuild = $schema;
    }

    /**
     * @return void
     */
    public static function clear(): void
    {
        self::$activeBuild = null;
    }

}
