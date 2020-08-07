<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Field\Field;

interface ResolverProvider
{
    /**
     * @return int
     */
    public static function getPriority(): int;

    /**
     * @param string|null $typeName
     * @param Field|null $field
     * @return string|null
     */
    public static function getResolverMethod(?string $typeName = null, ?Field $field = null): ?string;
}
