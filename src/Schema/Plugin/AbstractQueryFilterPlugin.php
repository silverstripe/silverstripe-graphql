<?php


namespace SilverStripe\GraphQL\Schema\Plugin;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterRegistry;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FilterRegistryInterface;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\ListFieldFilterInterface;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Type\ModelType;

/**
 * Generic plugin that can be used for filter inputs
 */
abstract class AbstractQueryFilterPlugin extends AbstractNestedInputPlugin implements SchemaUpdater
{

    /**
     * @var string
     * @config
     */
    private static $field_name = 'filter';

    /**
     * @return string
     */
    protected function getFieldName(): string
    {
        return $this->config()->get('field_name');
    }

    /**
     * Creates all the { eq: String, lte: String }, { eq: Int, lte: Int } etc types for comparisons
     * @param Schema $schema
     * @throws SchemaBuilderException
     */
    public static function updateSchema(Schema $schema): void
    {
        /* @var FieldFilterRegistry $registry */
        $registry = Injector::inst()->get(FilterRegistryInterface::class);
        $filters = $registry->getAll();
        if (empty($filters)) {
            return;
        }
        foreach (Schema::getInternalTypes() as $typeName) {
            $type = InputType::create(static::getLeafNodeType($typeName));
            foreach ($filters as $id => $filterInstance) {
                if ($filterInstance instanceof ListFieldFilterInterface) {
                    $type->addField($id, "[{$typeName}]");
                } else {
                    $type->addField($id, $typeName);
                }
            }
            $schema->addType($type);
        }
    }

    /**
     * @param ModelType $modelType
     * @return string
     */
    public static function getTypeName(ModelType $modelType): string
    {
        $modelTypeName = $modelType->getModel()->getTypeName();
        return $modelTypeName . 'FilterFields';
    }

    /**
     * @param string $internalType
     * @return string
     */
    protected static function getLeafNodeType(string $internalType): string
    {
        return sprintf('QueryFilter%sComparator', $internalType);
    }

}
