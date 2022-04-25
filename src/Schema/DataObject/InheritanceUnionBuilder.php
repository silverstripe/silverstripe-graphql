<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryCollector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\ModelUnionType;
use SilverStripe\ORM\DataObject;
use ReflectionException;

/**
 * A schema-aware services for DataObject model types that creates union types
 * for all the members of an inheritance chain. Can also apply these unions
 * to queries to enforce unions when return types have descendants.
 */
class InheritanceUnionBuilder
{
    use Injectable;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * InheritanceUnionBuilder constructor.
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->setSchema($schema);
    }

    /**
     * @param ModelType $modelType
     * @return void
     * @throws ReflectionException
     * @throws SchemaBuilderException
     * @return $this
     */
    public function createUnions(ModelType $modelType): InheritanceUnionBuilder
    {
        $schema = $this->getSchema();
        $chain = InheritanceChain::create($modelType->getModel()->getSourceClass());
        if (!$chain->hasDescendants()) {
            return $this;
        }
        $name = static::unionName($modelType->getName(), $schema->getConfig());
        $union = ModelUnionType::create($modelType, $name);

        $types = array_filter(array_map(function ($class) use ($schema) {
            if (!$schema->getModelByClassName($class)) {
                return null;
            }
            return $schema->getConfig()->getTypeNameForClass($class);
        }, $chain->getDescendantModels() ?? []));

        if (empty($types)) {
            return $this;
        }

        $types[] = $modelType->getName();

        $union->setTypes($types);
        $union->setTypeResolver([AbstractTypeResolver::class, 'resolveType']);
        $schema->addUnion($union);
        return $this;
    }

    /**
     * Changes all queries to use inheritance unions where applicable
     * @param ModelType $modelType
     * @throws SchemaBuilderException
     * @return $this
     */
    public function applyUnionsToQueries(ModelType $modelType): InheritanceUnionBuilder
    {
        $schema = $this->getSchema();
        $queryCollector = QueryCollector::create($schema);

        /* @var ModelQuery $query */
        foreach ($queryCollector->collectQueriesForType($modelType) as $query) {
            $typeName = $query->getNamedType();
            $modelType = $schema->getModel($typeName);
            // Type was customised. Ignore.
            if (!$modelType) {
                continue;
            }
            if (!$modelType->getModel() instanceof DataObjectModel) {
                continue;
            }

            $unionName = static::unionName($modelType->getName(), $schema->getConfig());
            if ($union = $schema->getUnion($unionName)) {
                $query->setNamedType($unionName);
            }
        }

        return $this;
    }

    /**
     * @param string $modelName
     * @param SchemaConfig $schemaConfig
     * @return string
     * @throws SchemaBuilderException
     */
    public static function unionName(string $modelName, SchemaConfig $schemaConfig): string
    {
        $callable = $schemaConfig->get(
            'inheritanceUnionBuilder.name_formatter',
            [static:: class, 'defaultUnionFormatter']
        );
        return $callable($modelName);
    }

    /**
     * @param string $modelName
     * @return string
     */
    public static function defaultUnionFormatter(string $modelName): string
    {
        return $modelName . 'InheritanceUnion';
    }


    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @param Schema $schema
     * @return InheritanceUnionBuilder
     */
    public function setSchema(Schema $schema): InheritanceUnionBuilder
    {
        $this->schema = $schema;
        return $this;
    }
}
