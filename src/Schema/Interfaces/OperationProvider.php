<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Field\Field;

/**
 * Implementors of this interface provide a lookup for operations by identifiers
 */
interface OperationProvider
{
    /**
     * @param string $id
     * @return Field|null
     */
    public function getOperationCreatorByIdentifier(string $id): ?OperationCreator;

    /**
     * @return string[]
     */
    public function getAllOperationIdentifiers(): array;
}
