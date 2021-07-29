<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\GraphQL\Schema\Type\Enum;

class DBCurrencyArgs extends DBDecimalArgs
{

    public function getEnum(): Enum
    {
        return Enum::create(
            'DBCurrencyFormattingOption',
            $this->getValues(),
            'Formatting options for fields that map to DBCurrency data types'
        );
    }

    public function getValues(): array
    {
        return array_merge(
            parent::getValues(),
            [
                'WHOLE' => 'Whole',
            ]
        );
    }
}
