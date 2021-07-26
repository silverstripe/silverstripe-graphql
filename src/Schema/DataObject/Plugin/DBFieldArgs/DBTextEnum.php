<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;


use SilverStripe\GraphQL\Schema\Type\Enum;

class DBTextEnum extends DBStringEnum
{
    private $name = 'DBTextFormattingOption';

    private $description = 'Formatting options for fields that map to DBText data types';

    public function getValues()
    {
        return array_merge(parent::getValues(), [
            'BIG_SUMMARY' => 'BigSummary',
            'CONTEXT_SUMMARY' => 'ContextSummary',
            'FIRST_PARAGRAPH' => 'FirstParagraph',
            'LIMIT_SENTENCES' => 'LimitSentences',
            'SUMMARY' => 'Summary',
        ]);
    }
}
