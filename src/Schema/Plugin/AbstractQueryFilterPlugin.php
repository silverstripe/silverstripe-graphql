<?php


namespace SilverStripe\GraphQL\Schema\Plugin;

use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterRegistry;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FilterRegistryInterface;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\ListFieldFilterInterface;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Services\NestedInputBuilder;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Type\Type;

/**
 * Generic plugin that can be used for filter inputs
 */
abstract class AbstractQueryFilterPlugin implements SchemaUpdater, ModelQueryPlugin
{
    use Injectable;
    use Configurable;

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
     * @throws NotFoundExceptionInterface
     */
    public static function updateSchema(Schema $schema): void
    {
        /* @var FieldFilterRegistry $registry */
        $registry = Injector::inst()->get(FilterRegistryInterface::class);
        $filters = $registry->getAll();
        if (empty($filters)) {
            return;
        }
        $scalarTypes = Schema::getInternalTypes();
        foreach ($schema->getEnums() as $enum) {
            $scalarTypes[] = $enum->getName();
        }
        foreach ($scalarTypes as $typeName) {
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
     * @param ModelQuery $query
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(ModelQuery $query, Schema $schema, array $config = []): void
    {
        $fields = $config['fields'] ?? Schema::ALL;
        $resolvers = $config['resolve'] ?? [];
        $builder = NestedInputBuilder::create($query, $schema, $fields, $resolvers);
        $this->updateInputBuilder($builder);
        $builder->populateSchema();
        if (!$builder->getRootType()) {
            return;
        }
        $query->addArg($this->getFieldName(), $builder->getRootType()->getName());
        $canonicalType = $schema->getCanonicalType($query->getNamedType());
        $rootType = $canonicalType ? $canonicalType->getName() : $query->getNamedType();
        $resolvers = $builder->getResolvers();
        $context = [
            'fieldName' => $this->getFieldName(),
            'rootType' => $rootType,
        ];
        if (!empty($resolvers)) {
            $context['resolvers'] = $resolvers;
        }

        $query->addResolverAfterware(
            $this->getResolver($config),
            $context
        );
    }

    /**
     * @param Type $type
     * @return string
     */
    public static function getTypeName(Type $type): string
    {
        $modelTypeName = $type->getName();
        return $modelTypeName . 'FilterFields';
    }

    /**
     * @param string $internalType
     * @return string
     */
    public static function getLeafNodeType(string $internalType): string
    {
        return sprintf('QueryFilter%sComparator', $internalType);
    }

    /**
     * @param NestedInputBuilder $builder
     */
    protected function updateInputBuilder(NestedInputBuilder $builder): void
    {
        $builder->setLeafNodeHandler([static::class, 'getLeafNodeType'])
            ->setTypeNameHandler([static::class, 'getTypeName']);
    }

    abstract protected function getResolver(array $config): callable;
}
