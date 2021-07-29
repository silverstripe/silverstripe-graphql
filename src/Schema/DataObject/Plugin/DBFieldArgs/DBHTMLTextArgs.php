<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Exception;
use SilverStripe\ORM\FieldType\DBString;

class DBHTMLTextArgs extends DBTextArgs
{
    public function applyToField(ModelField $field): void
    {
        parent::applyToField($field);
        $field->addArg('parseShortcodes', [
            'type' => 'Boolean',
            'description' => 'Parse shortcodes if true, do not parse if false.
                If null, fallback on schema config setting',
        ]);
    }

    /**
     * @param DBString $obj
     * @param array $args
     * @return DBField
     * @throws Exception
     */
    public static function resolve(DBString $obj, array $args)
    {
        /* @var DBHTMLText $obj */
        $parse = $args['parseShortcodes'] ?? null;
        if ($parse !== null) {
            $obj->setProcessShortcodes($parse);
        }

        return parent::resolve($obj, $args);
    }
}
