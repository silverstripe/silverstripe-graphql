<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Type\Enum;
use Exception;
use SilverStripe\ORM\FieldType\DBText;

class DBTextArgs extends DBFieldArgs
{
    public function getEnum(): Enum
    {
        return Enum::create(
            'DBTextFormattingOption',
            $this->getValues(),
            'Formatting options for fields that map to DBText data types'
        );
    }

    public function getValues(): array
    {
        return [
            'CONTEXT_SUMMARY' => 'ContextSummary',
            'FIRST_PARAGRAPH' => 'FirstParagraph',
            'LIMIT_SENTENCES' => 'LimitSentences',
            'SUMMARY' => 'Summary',
        ];
    }

    public function applyToField(ModelField $field): void
    {
        $field
            ->addArg('format', [
                'type' => $this->getEnum()->getName(),
                'description' => 'Formatting options for this field',
            ])
            ->addArg('limit', [
                'type' => 'Int',
                'description' => 'An optional limit for the formatting option',
            ])
            ->addResolverAfterware(
                $this->getResolver()
            );
    }

    /**
     * @return callable
     */
    protected function getResolver(): callable
    {
        return [static::class, 'resolve'];
    }

    /**
     * @param mixed $obj
     * @return mixed
     */
    public static function resolve($obj, array $args, array $context)
    {
        if (!$obj instanceof DBText) {
            return $obj;
        }
        $format = $args['format'] ?? null;
        $limit = $args['limit'] ?? null;

        if (!$format) {
            return $obj;
        }

        $noArgMethods = ['FirstParagraph'];

        if ($limit && in_array($format, $noArgMethods ?? [])) {
            throw new Exception(sprintf('Arg "limit" is not allowed for format "%s"', $format));
        }

        $result = DBFieldArgs::baseFormatResolver($obj, $args);

        // If no referential equality, the parent did something, so we're done.
        if ($result !== $obj) {
            return $result;
        }

        if ($format) {
            $args = $limit === null ? [] : [$limit];
            if ($obj->hasMethod($format)) {
                return $obj->obj($format, $args);
            }
        }

        return $obj;
    }
}
