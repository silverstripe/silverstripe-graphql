<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Control\HTTP;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Exception;

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
     * @param mixed $obj
     * @param array $args
     * @param array $context
     * @return DBField
     * @throws Exception
     */
    public static function resolve($obj, array $args, array $context)
    {
        $result = parent::resolve($obj, $args, $context);
        if (!$result instanceof DBHTMLText) {
            return $result;
        }

        /* @var DBHTMLText $obj */
        $parse = $args['parseShortcodes'] ?? null;
        if ($parse === null) {
            $config = SchemaConfigProvider::get($context);
            if ($config) {
                $parse = $config->getModelConfiguration('DataObject')->get('parseShortcodes', true);
            }
        }
        $obj->setProcessShortcodes($parse);

        return $obj->RAW();
    }
}
