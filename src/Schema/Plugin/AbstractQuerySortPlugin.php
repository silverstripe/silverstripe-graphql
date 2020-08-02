<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\ModelType;

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
    public static function updateSchemaOnce(Schema $schema): void
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
     * @param Query $query
     * @param ModelType $modelType
     * @param array $path
     * @return string
     */
    public static function getTypeName(Query $query, ModelType $modelType, array $path = []): string
    {
        $pathNames = array_map('ucfirst', $path);
        return sprintf(
            '%s%s%s',
            ucfirst($query->getName()),
            implode('', $pathNames),
            'SortFields'
        );
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
