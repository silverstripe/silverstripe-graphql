<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Schema\DataObject\Fields\FieldCreator;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\ModelFieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;

class DBFieldArgsPlugin implements SchemaUpdater, ModelFieldPlugin
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
        foreach (static::config()->get('field_creators') as $creator) {
            Schema::invaraint(
                is_subclass_of($creator, FieldCreator::class),
                'Class %s must be an implementation of %s on %s',
                $creator,
                FieldCreator::class,
                static::class
            );

            $creator::updateSchema($schema);
        }
    }

    public function apply(ModelField $field, Schema $schema, array $config = []): void
    {

    }
}
