<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\ORM\FieldType\DBDate;
use Exception;
use SilverStripe\ORM\FieldType\DBField;

class DBDateArgs extends DBFieldArgs
{
    public function getEnum(): Enum
    {
        return Enum::create(
            'DBDateFormattingOptions',
            $this->getValues(),
            'Formatting options for fields that map to DBDate data types'
        );
    }

    public function applyToField(ModelField $field): void
    {
        $field
            ->addArg('format', [
                'type' => $this->getEnum()->getName(),
                'description' => 'Formatting options for this field',
            ])
            ->addArg('customFormat', [
                'type' => 'String',
                'description' => 'If format is CUSTOM, the format string, e.g. "y-MM-dd HH:mm:ss"',
            ])
            ->addResolverAfterware($this->getResolver());
    }

    protected function getResolver(): callable
    {
        return [static::class, 'resolve'];
    }

    /**
     * @param mixed $obj
     * @param array $args
     * @return DBField | string
     * @throws Exception
     */
    public static function resolve($obj, array $args)
    {
        if (!$obj instanceof DBDate) {
            return $obj;
        }
        $format = $args['format'] ?? null;
        $custom = $args['customFormat'] ?? null;

        if ($format === 'Format') {
            if (!$custom) {
                throw new Exception('The "custom" option requires a value for "customFormat"');
            }
            return $obj->Format($custom);
        }
        if ($custom) {
            throw new Exception('The "customFormat" argument should not be set for formats that are not "custom"');
        }

        if ($obj->hasMethod($format)) {
            return $obj->obj($format);
        }

        return $obj;
    }

    public function getValues(): array
    {
        return [
            'TIMESTAMP' => 'Timestamp',
            'NICE' => 'Nice',
            'DAY_OF_WEEK' => 'DayOfWeek',
            'MONTH' => 'Month',
            'YEAR' => 'Year',
            'SHORT_MONTH' => 'ShortMonth',
            'DAY_OF_MONTH' => 'DayOfMonth',
            'SHORT' => 'Short',
            'LONG' => 'Long',
            'FULL' => 'Full',
            'CUSTOM' => 'Format',
        ];
    }
}
