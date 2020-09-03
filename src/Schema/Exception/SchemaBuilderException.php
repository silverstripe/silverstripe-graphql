<?php


namespace SilverStripe\GraphQL\Schema\Exception;

use Exception;

/**
 * The primary exception thrown by the Schema and its components. Used whenever
 * the schema is put into a state where it cannot build a valid schema.
 */
class SchemaBuilderException extends Exception
{
}
