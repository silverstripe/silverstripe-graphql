<?php


namespace SilverStripe\GraphQL\Schema\Plugin;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Services\NestedInputBuilder;
use SilverStripe\GraphQL\Schema\Traits\SortTrait;
use SilverStripe\GraphQL\Schema\Type\InputType;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Model\List\SS_List;

class SortPlugin implements FieldPlugin, SchemaUpdater
{
    use Configurable;
    use Injectable;
    use SortTrait;

    const IDENTIFIER = 'sorter';

    /**
     * @config
     */
    private static string $field_name = 'sort';

    /**
     * @config
     * @var callable
     */
    private static $resolver = [__CLASS__, 'sort'];

    public function getIdentifier(): string
    {
        return SortPlugin::IDENTIFIER;
    }

    public static function updateSchema(Schema $schema): void
    {
        AbstractQuerySortPlugin::updateSchema($schema);
    }

    /**
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
            SortPlugin::getIdentifier()
        );
        $sortFieldName = $this->config()->get('field_name');
        $input = InputType::create($name);
        foreach ($fields as $fieldName => $data) {
            if ($data === false) {
                continue;
            }
            if ($data === true) {
                $input->addField($fieldName, 'SortDirection');
            } elseif (is_string($data)) {
                $input->addField($fieldName, 'SortDirection');
            }
        }

        $field->addResolverAfterware(
            $this->getResolver($config),
            [
                'rootType' => $field->getNamedType(),
                'fieldName' => $sortFieldName,
            ]
        );
        $schema->addType($input);
        $field->addArg($sortFieldName, $input->getName());
    }

    public static function sort(array $context): Closure
    {
        $fieldName = $context['fieldName'];
        return function (?SS_List $list, array $args, array $context, ResolveInfo $info) use ($fieldName) {
            if ($list === null) {
                return null;
            }

            if (!isset($args[$fieldName])) {
                return $list;
            }

            $sortArgs = SortPlugin::getSortArgs($info, $args, $fieldName);
            $list = $list->sort($sortArgs);

            return $list;
        };
    }

    /**
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
