<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface ResolverProvider
{
    /**
     * @return int
     */
    public static function getPriority(): int;

    /**
     * @param string|null $typeName
     * @param string|null $fieldName
     * @return string|null
     */
    public static function getResolverMethod(?string $typeName = null, ?string $fieldName = null): ?string;
}
