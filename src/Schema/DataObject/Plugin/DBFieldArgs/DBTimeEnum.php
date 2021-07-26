<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;


use SilverStripe\GraphQL\Schema\Type\Enum;

class DBTimeEnum extends Enum
{
    private $name = 'DBTimeFormattingOptions';

    private $description = 'Formatting options for fields that map to DBTime data types';

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
            'SHORT' => 'Short',
            'CUSTOM' => 'CUSTOM',
        ];
    }

}
