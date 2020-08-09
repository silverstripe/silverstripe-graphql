<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Type;

/**
 * Generic pagination functionality for a query that can be customised in subclasses
 */
abstract class AbstractPaginationPlugin implements FieldPlugin, SchemaUpdater
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
     * @param Query $field
     * @param Schema $schema
     * @param array|null $config
     * @throws SchemaBuilderException
     */
    public function apply(Field $field, Schema $schema, array $config = []): void
    {
        $defaultLimit = $config['defaultLimit'] ?? $this->config()->get('default_limit');
        $max = $this->config()->get('max_limit');
        $limit = min($defaultLimit, $max);
        $field->addArg('limit', "Int = $limit")
            ->addArg('offset', "Int = 0")
            ->addResolverAfterware(
                $this->getPaginationResolver(),
                ['maxLimit' => $max]
            );

        // Set the new return type
        $plainType = $field->getNamedType();
        $field->setType($field->getName() . 'Connection');

        // Create the edge type for this query
        $edgeType = Type::create($field->getName() . 'Edge')
            ->setDescription('The collections edge')
            ->addField('node', $plainType, function (Field $field) {
                $field->setResolver([static::class, 'noop'])
                    ->setDescription('The node at the end of the collections edge');
            });
        $schema->addType($edgeType);

        // Create the connection type for this query
        $connectionType = Type::create($field->getName() . 'Connection')
            ->addField('edges', "[{$edgeType->getName()}]!")
            ->addField('nodes', "[$plainType]!")
            ->addField('pageInfo', 'PageInfo!');

        $schema->addType($connectionType);
    }

    /**
     * "node" is just structural and should use a noop
     *
     * @param $obj
     * @return mixed
     */
    public static function noop ($obj) {
        return $obj;
    }
}
