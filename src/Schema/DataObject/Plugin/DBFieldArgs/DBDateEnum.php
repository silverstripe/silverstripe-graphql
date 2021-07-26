<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;


use SilverStripe\GraphQL\Schema\Type\Enum;

class DBDateEnum extends Enum
{
    private $name = 'DBDateFormattingOptions';

    private $description = 'Formatting options for fields that map to DBDate data types';

    public function __construct()
    {
        parent::__construct(
            $this->getName(),
            $this->getValues(),
            $this->getDescription()
        );
    }

    public function getValues()
    {
        return [
            'TIMESTAMP' => 'Timestamp',
            'NICE' => 'Nice',
            'DAY_OF_WEEK' => 'DayOfWeek',
            'MONTH' => 'Month',
            'YEAR' => 'Year',
            'SHORT_MONTH' => 'ShortMonth',
            'DAY_OF_MONTH' => 'DayOfMonth',
            'SHORT' => 'Short',
            'LONG' => 'Long',
            'FULL' => 'Full',
            'CUSTOM' => 'CUSTOM',
        ];
    }

}
