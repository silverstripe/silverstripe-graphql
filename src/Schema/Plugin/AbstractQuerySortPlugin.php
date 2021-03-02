<?php


namespace SilverStripe\GraphQL\Schema\Plugin;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Services\NestedInputBuilder;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Type;

/**
 * Generic plugin that can be used to add sort paramaters to a query
 */
abstract class AbstractQuerySortPlugin implements SchemaUpdater, ModelQueryPlugin
{
    use Injectable;
    use Configurable;

    /**
     * @var string
     * @config
     */
    private static $field_name = 'sort';

    /**
     * @param ModelQuery $query
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(ModelQuery $query, Schema $schema, array $config = []): void
    {
        $fields = $config['fields'] ?? Schema::ALL;
        $builder = NestedInputBuilder::create($query, $schema, $fields);
        $this->updateInputBuilder($builder);
        $builder->populateSchema();
        if (!$builder->getRootType()) {
            return;
        }
        $query->addArg($this->getFieldName(), $builder->getRootType()->getName());
        $canonicalType = $schema->getCanonicalType($query->getNamedType());
        $rootType = $canonicalType ? $canonicalType->getName() : $query->getNamedType();
        $query->addResolverAfterware(
            $this->getResolver($config),
            [
                'fieldName' => $this->getFieldName(),
                'rootType' => $rootType,
            ]
        );
    }

    /**
     * @param NestedInputBuilder $builder
     */
    protected function updateInputBuilder(NestedInputBuilder $builder): void
    {
        $builder->setLeafNodeHandler([static::class, 'getLeafNodeType'])
            ->setTypeNameHandler([static::class, 'getTypeName']);
    }

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
     * @param Type $type
     * @return string
     */
    public static function getTypeName(Type $type): string
    {
        return $type->getName() . 'SortFields';
    }


    /**
     * @param string $internalType
     * @return string
     */
    public static function getLeafNodeType(string $internalType): string
    {
        return 'SortDirection';
    }

    /**
     * @param array $config
     * @return array
     */
    abstract protected function getResolver(array $config): array;
}
