<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Type\ModelType;

/**
 * Generic plugin that can be used to add sort paramaters to a query
 */
abstract class AbstractQuerySortPlugin extends AbstractNestedInputPlugin implements SchemaUpdater
{
    /**
     * @var string
     * @config
     */
    private static $field_name = 'sort';

    /**
     * @return string
     */
    protected function getFieldName(): string
    {
        return $this->config()->get('field_name');
    }


    /**
     * @param Schema $schema
     */
    public static function updateSchema(Schema $schema): void
    {
        $type = Enum::create(
            'SortDirection',
            [
                'ASC' => 'ASC',
                'DESC' => 'DESC',
            ]
        );
        $schema->addEnum($type);
    }

    /**
     * @param ModelType $modelType
     * @return string
     */
    public static function getTypeName(ModelType $modelType): string
    {
        $modelTypeName = $modelType->getModel()->getTypeName();
        return $modelTypeName . 'SortFields';
    }


    /**
     * @param string $internalType
     * @return string
     */
    protected static function getLeafNodeType(string $internalType): string
    {
        return 'SortDirection';
    }

}
