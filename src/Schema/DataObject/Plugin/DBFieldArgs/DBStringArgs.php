<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Type\Enum;

class DBStringArgs extends DBFieldArgs
{
    public function getEnum(): Enum
    {
        return Enum::create(
            'DBStringFormattingOptions',
            $this->getValues(),
            'Formatting options for fields that map to DBString data types'
        );
    }

    public function getValues(): array
    {
        return [
            'NICE' => 'Nice',
            'INT' => 'Int',
        ];
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
}
