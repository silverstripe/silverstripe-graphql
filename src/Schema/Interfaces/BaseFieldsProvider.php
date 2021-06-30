<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Defines a model that provides required fields for all the types it creates
 */
interface BaseFieldsProvider
{
    /**
     * Fields that must appear on all implementations
     * @return array
     */
    public function getBaseFields(): array;
}
