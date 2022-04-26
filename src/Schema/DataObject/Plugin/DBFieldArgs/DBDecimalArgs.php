<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Type\Enum;

class DBDecimalArgs extends DBFieldArgs
{
    public function getEnum(): Enum
    {
        return Enum::create(
            'DBDecimalFormattingOptions',
            $this->getValues(),
            'Formatting options for fields that map to DBDecimal data types'
        );
    }

    public function applyToField(ModelField $field): void
    {
        $field->addArg('format', [
            'type' => $this->getEnum()->getName(),
            'description' => 'Formatting options for this field',
        ])->addResolverAfterware(
            $this->getResolver()
        );
    }

    protected function getResolver(): callable
    {
        return [DBFieldArgs::class, 'baseFormatResolver'];
    }


    public function getValues(): array
    {
        return [
            'INT' => 'Int',
        ];
    }
}
