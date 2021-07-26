<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;


use SilverStripe\GraphQL\Schema\Type\Enum;

class DBDecimalEnum extends Enum
{
    private $name = 'DBDecimalFormattingOptions';

    private $description = 'Formatting options for fields that map to DBDecimal data types';

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
        ];
    }

}
