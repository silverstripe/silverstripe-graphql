<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;


class DBDatetimeEnum extends DBDateEnum
{
    private $name = 'DBDatetimeFormattingOption';

    private $description = 'Formatting options for fields that map to DBDatetime data types';

    public function getValues()
    {
        return array_merge(parent::getValues(), [
            'DATE' => 'Date',
            'TIME' => 'Time',
            'TIME12' => 'Time12',
            'TIME24' => 'Time24',
        ]);
    }

}
