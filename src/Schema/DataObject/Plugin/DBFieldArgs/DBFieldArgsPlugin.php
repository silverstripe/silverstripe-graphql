<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Interfaces\ModelTypePlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;

class DBFieldArgsPlugin implements SchemaUpdater, ModelTypePlugin
{
    const IDENTIFIER = 'dbFieldArgs';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param Schema $schema
     */
    public static function updateSchema(Schema $schema): void
    {
        $schema
            ->addEnum(DBTextArgs::create()->getEnum())
            ->addEnum(DBHTMLTextArgs::create()->getEnum())
            ->addEnum(DBDecimalArgs::create()->getEnum())
            ->addEnum(DBFloatArgs::create()->getEnum())
            ->addEnum(DBDateArgs::create()->getEnum())
            ->addEnum(DBDatetimeArgs::create()->getEnum())
            ->addEnum(DBTimeArgs::create()->getEnum());
    }

    /**
     * @param ModelType $type
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(ModelType $type, Schema $schema, array $config = []): void
    {
        foreach ($type->getFields() as $field) {
            if ($field instanceof ModelField && $field->getModel() instanceof DataObjectModel) {
                $dataClass = $field->getMetadata()->get('dataClass');
                if (!$dataClass) {
                    continue;
                }
                $argFactory = Config::forClass($dataClass)->get('graphql_args');
                if ($argFactory) {
                    /* @var DBFieldArgs $inst */
                    $inst = Injector::inst()->create($argFactory);
                    $inst->applyToField($field);
                }
            }
        }
    }
}
