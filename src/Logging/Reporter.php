<?php

namespace SilverStripe\GraphQL\Logging;

use Monolog\Formatter\FormatterInterface;
use SilverStripe\Control\Director;

class Reporter implements FormatterInterface
{
    public function format(array $record)
    {
        $message = $record['message'] ?? '';
        $break = Director::is_cli() ? "\n" : '<br>';

        return sprintf('%s%s', $message, $break);
    }

    public function formatBatch(array $records)
    {
        $output = [];
        foreach ($records as $record) {
            $output[] = $this->format($record);
        }

        return implode('', $output);
    }
}
