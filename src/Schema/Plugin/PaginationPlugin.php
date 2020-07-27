<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Interfaces\QueryPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Type;

abstract class PaginationPlugin implements QueryPlugin, SchemaUpdater
{
    use Configurable;

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

    abstract protected function getPaginationResolver();

    /**
     * @param Schema $schema
     * @throws SchemaBuilderException
     */
    public static function updateSchemaOnce(Schema $schema): void
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
     * @param Query $query
     * @param Schema $schema
     * @param array|null $config
     * @throws SchemaBuilderException
     */
    public function apply(Query $query, Schema $schema, array $config = []): void
    {
        $defaultLimit = $config['defaultLimit'] ?? $this->config()->get('default_limit');
        $max = $this->config()->get('max_limit');
        $limit = min($defaultLimit, $max);
        $query->addArg('limit', "Int = $limit")
            ->addArg('offset', "Int = 0")
            ->addResolverMiddleware(
                $this->getPaginationResolver(),
                ['maxLimit' => $max]
            );

        // Set the new return type
        $plainType = $query->getNamedType();
        $query->setType($query->getName() . 'Connection');

        // Create the edge type for this query
        $edgeType = Type::create($query->getName() . 'Edge')
            ->setDescription('The collections edge')
            ->addField('node', $plainType, function (Field $field) {
                $field->setResolver([static::class, 'noop'])
                    ->setDescription('The node at the end of the collections edge');
            });
        $schema->addType($edgeType);

        // Create the connection type for this query
        $connectionType = Type::create($query->getName() . 'Connection')
            ->addField('edges', "[{$edgeType->getName()}]!")
            ->addField('nodes', "[$plainType]!")
            ->addField('pageInfo', 'PageInfo!');

        $schema->addType($connectionType);
    }

    /**
     * @param $obj
     * @return mixed
     */
    public static function noop ($obj) {
        return $obj;
    }
}
