<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;


use SilverStripe\GraphQL\Schema\Type\Enum;

class DBFloatEnum extends Enum
{
    private $name = 'DBFloatFormattingOptions';

    private $description = 'Formatting options for fields that map to DBFloat data types';

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
            'ROUND' => 'Round',
            'NICE_ROUND' => 'NiceRound',
        ];
    }

}
