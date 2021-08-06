<?php


namespace SilverStripe\GraphQL\QueryHandler;

/**
 * Throws everything, including notices, so the JSON response doesn't get corruped by E_NOTICE, E_WARN
 * outputs.
 */
class DevErrorHandler
{
    /**
     * @param $severity
     * @param $message
     * @param $filename
     * @param $line
     * @return false
     * @throws QueryException
     */
    public static function handleError($severity, $message, $filename, $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new QueryException($message);
    }
}
