<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QuerySort;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InputType;

class SortPlugin implements FieldPlugin, SchemaUpdater
{
    use Configurable;
    use Injectable;

    const IDENTIFIER = 'sorter';

    /**
     * @var string
     * @config
     */
    private static $field_name = 'sort';

    /**
     * @var array
     * @config
     */
    private static $resolver = [QuerySort::class, 'sort'];

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param Schema $schema
     */
    public static function updateSchema(Schema $schema): void
    {
        AbstractQuerySortPlugin::updateSchema($schema);
    }

    /**
     * @param Field $field
     * @param Schema $schema
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function apply(Field $field, Schema $schema, array $config = []): void
    {
        $name = $config['input'] ?? $field->getName() . 'SimpleSortFields';
        $fields = $config['fields'];
        Schema::assertValidConfig($fields);
        Schema::invariant(
            !empty($fields),
            '%s requires a "fields" parameter to be passed to its config that maps field name to type name',
            self::getIdentifier()
        );
        $sortFieldName = $this->config()->get('field_name');
        $input = InputType::create($name);
        $mapping = [];
        foreach ($fields as $fieldName => $data) {
            if ($data === false) {
                continue;
            }
            if ($data === true) {
                $input->addField($fieldName, 'SortDirection');
            } elseif (is_string($data)) {
                $input->addField($fieldName, 'SortDirection');
                $mapping[$fieldName] = $data;
            }
        }

        $field->addResolverAfterware(
            $this->getResolver($config),
            [
                'fieldMapping' => $mapping,
                'fieldName' => $sortFieldName,
            ]
        );
        $schema->addType($input);
        $field->addArg($sortFieldName, $input->getName());
    }

    /**
     * @return array
     * @param array $config
     * @throws SchemaBuilderException
     */
    protected function getResolver(array $config): array
    {
        $resolver = $config['resolver'] ?? $this->config()->get('resolver');
        Schema::invariant(
            $resolver,
            '%s has no resolver defined',
            __CLASS__
        );

        return ResolverReference::create($resolver)->toArray();
    }

}
