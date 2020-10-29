<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Base plugin interface. There is a lot of "duck programming" happening in this API
 * that will go away once we have better type variance in PHP 7.4:
 * https://wiki.php.net/rfc/covariant-returns-and-contravariant-parameters
 *
 * Ideally, this interface would provide apply(SchemaComponent) and implementations could
 * do apply(ModelQuery) using type variance, meaning we could get rid of virtually
 * all PluginInterface descendants.
 */
interface PluginInterface
{
    /**
     * @return string
     */
    public function getIdentifier(): string;
}
