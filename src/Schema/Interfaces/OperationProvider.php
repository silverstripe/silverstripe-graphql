<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Field\Field;

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
