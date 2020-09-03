<?php


namespace SilverStripe\GraphQL\Schema\Exception;

use Exception;

/**
 * Thrown when an operation encounters a permissions problem, e.g. lack of read/write
 * permissions
 */
class PermissionsException extends Exception
{
}
