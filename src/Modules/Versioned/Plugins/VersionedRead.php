<?php


namespace SilverStripe\GraphQL\Modules\Versioned\Plugins;

use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Modules\Versioned\Resolvers\VersionedResolver;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Model\ModelData;

// GraphQL dependency is optional in versioned,
// and the following implementation relies on existence of this class (in GraphQL v4)
if (!interface_exists(ModelQueryPlugin::class)) {
    return;
}

class VersionedRead implements ModelQueryPlugin
{
    const IDENTIFIER = 'readVersion';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return VersionedRead::IDENTIFIER;
    }

    /**
     * @param ModelQuery $query
     * @param Schema $schema
     * @param array $config
     */
    public function apply(ModelQuery $query, Schema $schema, array $config = []): void
    {
        $class = $query->getModel()->getSourceClass();
        if (!ModelData::has_extension($class, Versioned::class)) {
            return;
        }

        // The versioned argument only affects global reading state. Should not
        // apply to nested queries.
        $rootQuery = $schema->getQueryType()->getFieldByName($query->getName());
        if (!$rootQuery) {
            return;
        }

        $query->addResolverAfterware([VersionedResolver::class, 'resolveVersionedRead']);
        $query->addArg('versioning', 'VersionedInputType');
    }
}
