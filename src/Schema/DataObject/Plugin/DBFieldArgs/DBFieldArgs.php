<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\Fields\FieldCreator;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\ModelFieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBString;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBTime;

class DBFieldArgs implements SchemaUpdater, ModelFieldPlugin
{
    use Configurable;

    const IDENTIFIER = 'dbFieldArgs';

    private static $field_creators = [

    ];

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public static function updateSchema(Schema $schema): void
    {
        $schema
            ->addEnum(DBStringEnum::create())
            ->addEnum(DBTextEnum::create())
            ->addEnum(DBDateEnum::create())
            ->addEnum(DBTimeEnum::create())
            ->addEnum(DBDatetimeEnum::create())
            ->addEnum(DBDecimalEnum::create())
            ->addEnum(DBFloatEnum::create())
            ->addEnum(DBCurrencyEnum::create());
    }

    public function apply(ModelField $field, Schema $schema, array $config = []): void
    {
        if ($field->isList()) {
            return;
        }

        $creator = $field->getMetadata()->get('creator');
        if (
            !$creator ||
            $creator !== DataObjectModel::class ||
            !is_subclass_of($creator, DataObjectModel::class)
        ) {
            return;
        }
        $dataClass = $field->getMetadata()->get('dataClass');
        if (!$dataClass || !is_subclass_of($dataClass, DBField::class)) {
            return;
        }
        $dbField = DBField::create_field($dataClass);

        if ($dbField instanceof DBText) {
            $field->addArg('format', [
                'type' => DBTextEnum::create()->getName(),
                'description' => 'Formatting options for this field',
            ]);
            $field->addArg('limit', [
                'type' => 'Int',
                'description' => 'An optional limit for the formatting option');
            return;
        }

        if ($dbField instanceof DBString) {
            $field->addArg('format', [
                'type' => DBStringEnum::create()->getName(),
                'description' => 'Formatting options for this field',
            ]);
            $field->addArg('limit', [
                'type' => 'Int',
                'description' => 'An optional limit for the formatting option');
            return;
        }

        if ($dbField instanceof DBDatetime) {
            $field->addArg('format', [
                'type' => DBDatetimeEnum::create()->getName(),
                'description' => 'Formatting options for this field',
            ]);
            $field->addArg('customFormat', [
                'type' => 'String',
                'description' => 'If format is CUSTOM, the format string, e.g. "y-MM-dd HH:mm:ss"',
            ]);
            return;
        }

        if ($dbField instanceof DBDate) {
            $field->addArg('format', [
                'type' => DBDateEnum::create()->getName(),
                'description' => 'Formatting options for this field',
            ]);
            $field->addArg('customFormat', [
                'type' => 'String',
                'description' => 'If format is CUSTOM, the format string, e.g. "y-MM-dd HH:mm:ss"',
            ]);
            return;
        }

        if ($dbField instanceof DBTime) {
            $field->addArg('format', [
                'type' => DBTimeEnum::create()->getName(),
                'description' => 'Formatting options for this field',
            ]);
            $field->addArg('customFormat', [
                'type' => 'String',
                'description' => 'If format is CUSTOM, the format string, e.g. "HH:mm:ss"',
            ]);
            return;
        }
        
    }
}
