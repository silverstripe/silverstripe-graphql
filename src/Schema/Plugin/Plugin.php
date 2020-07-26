<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Schema;

abstract class Plugin
{
    use Injectable;
    use Configurable;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var Field
     */
    private $field;

    /**
     * @var array
     */
    private $config;

    /**
     * @param Schema $schema
     * @param Field $field
     * @param array $config
     */
    public function __construct(Schema $schema, Field $field, array $config = [])
    {
        $this->schema = $schema;
        $this->field = $field;
        $this->config = $config;
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
