<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\GraphQL\Schema\Type\Enum;

class DBDatetimeArgs extends DBDateArgs
{
    public function getEnum(): Enum
    {
        return Enum::create(
            'DBDatetimeFormattingOption',
            $this->getValues(),
            'Formatting options for fields that map to DBDatetime data types'
        );
    }

    public function getValues(): array
    {
        return array_merge(
            parent::getValues(),
            [
                'DATE' => 'Date',
                'TIME' => 'Time',
                'TIME12' => 'Time12',
                'TIME24' => 'Time24',
            ]
        );
    }
}
