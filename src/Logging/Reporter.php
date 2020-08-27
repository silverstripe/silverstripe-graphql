<?php

namespace SilverStripe\GraphQL\Logging;

use Monolog\Formatter\FormatterInterface;
use SilverStripe\Control\Director;

/**
 * Custom formatter added to the logging interface provided to the Schema class
 * that allows it to log out its build progress.
 */
class Reporter implements FormatterInterface
{
    /**
     * @param array $record
     * @return string
     */
    public function format(array $record): string
    {
        $message = $record['message'] ?? '';
        $break = Director::is_cli() ? "\n" : '<br>';

        return sprintf('%s%s', $message, $break);
    }

    /**
     * @param array $records
     * @return string
     */
    public function formatBatch(array $records): string
    {
        $output = [];
        foreach ($records as $record) {
            $output[] = $this->format($record);
        }

        return implode('', $output);
    }
}
