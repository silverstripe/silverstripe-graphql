<?php


namespace SilverStripe\GraphQL\Schema\Plugin;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Type;
use Countable;

/**
 * Generic pagination functionality for a query that can be customised in subclasses
 */
class PaginationPlugin implements FieldPlugin, SchemaUpdater
{
    use Configurable;
    use Injectable;

    const IDENTIFIER = 'paginate';

    /**
     * @var int
     * @config
     */
    private static $default_limit = 100;

    /**
     * @var int
     * @config
     */
    private static $max_limit = 100;

    /**
     * @var array|null
     * @config
     */
    private static $resolver;

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @return array
     * @param array $config
     * @throws SchemaBuilderException
     */
    protected function getPaginationResolver(array $config): array
    {
        $resolver = $config['resolver'] ?? $this->config()->get('resolver');
        Schema::invariant(
            $resolver,
            '%s has no resolver defined',
            __CLASS__
        );

        return ResolverReference::create($resolver)->toArray();
    }

    /**
     * @param Schema $schema
     * @throws SchemaBuilderException
     */
    public static function updateSchema(Schema $schema): void
    {
        // Create the PageInfo type, which is universal
        $pageinfoType = Type::create('PageInfo')
            ->addField('totalCount', 'Int!')
            ->addField('hasNextPage', 'Boolean')
            ->addField('hasPreviousPage', 'Boolean')
            ->setDescription('Information about pagination in a connection.');

        $schema->addType($pageinfoType);
    }

    /**
     * @param Field $field
     * @param Schema $schema
     * @param array|null $config
     * @throws SchemaBuilderException
     */
    public function apply(Field $field, Schema $schema, array $config = []): void
    {
        $defaultLimit = $config['defaultLimit'] ?? $this->config()->get('default_limit');
        $connectionName = $config['connection'] ?? $field->getName();
        $max = $this->config()->get('max_limit');
        $limit = min($defaultLimit, $max);
        $field->addArg('limit', "Int = $limit")
            ->addArg('offset', "Int = 0")
            ->addResolverAfterware(
                $this->getPaginationResolver($config),
                ['maxLimit' => $max]
            );

        // Set the new return type
        $plainType = $field->getNamedType();
        $connectionName = ucfirst($connectionName) . 'Connection';
        $field->setType($connectionName);

        // Create the edge type for this query
        $edgeType = Type::create($connectionName . 'Edge')
            ->setDescription('The collections edge')
            ->addField('node', $plainType, function (Field $field) {
                $field->setResolver([static::class, 'noop'])
                    ->setDescription('The node at the end of the collections edge');
            });
        $schema->addType($edgeType);

        // Create the connection type for this query
        $connectionType = Type::create($connectionName)
            ->addField('edges', "[{$edgeType->getName()}]!")
            ->addField('nodes', "[$plainType]!")
            ->addField('pageInfo', 'PageInfo!');

        $schema->addType($connectionType);
    }

    /**
     * @param int $total
     * @param iterable $limitedResults
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function createPaginationResult(
        int $total,
        iterable $limitedResults,
        int $limit,
        int $offset
    ): array {
        $nextPage = false;
        $previousPage = false;

        // Flag prev-next page
        if ($limit && (($limit + $offset) < $total)) {
            $nextPage = true;
        }
        if ($offset > 0) {
            $previousPage = true;
        }
        return [
            'edges' => $limitedResults,
            'nodes' => $limitedResults,
            'pageInfo' => [
                'totalCount' => $total,
                'hasNextPage' => $nextPage,
                'hasPreviousPage' => $previousPage
            ]
        ];
    }

    /**
     * "node" is just structural and should use a noop
     *
     * @param $obj
     * @return mixed
     */
    public static function noop($obj)
    {
        return $obj;
    }
}
