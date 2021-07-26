<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;


use SilverStripe\GraphQL\Schema\Type\Enum;

abstract class DBStringEnum extends Enum
{
    private $name = 'DBStringFormattingOptions';

    private $description = 'Formatting options for fields that map to DBString data types';

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
            'NICE' => 'Nice',
            'INT' => 'Int',
        ];
    }
}
