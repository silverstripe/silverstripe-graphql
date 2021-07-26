<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;


class DBCurrencyEnum extends DBDecimalEnum
{
    private $name = 'DBCurrencyFormattingOption';

    private $description = 'Formatting options for fields that map to DBCurrency data types';

    public function getValues()
    {
        return array_merge(parent::getValues(), [
            'WHOLE' => 'Whole',
        ]);
    }

}
