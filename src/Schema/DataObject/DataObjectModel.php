<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Schema\OperationCreator;
use SilverStripe\GraphQL\Schema\OperationProvider;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\SchemaModelInterface;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;

class DataObjectModel implements SchemaModelInterface, OperationProvider
{
    use Injectable;
    use Configurable;

    /**
     * @var array
     * @config
     */
    private $operations = [];

    /**
     * @var DataObject
     */
    private $dataObject;

    /**
     * DataObjectModel constructor.
     * @param string $class
     * @throws SchemaBuilderException
     */
    public function __construct(string $class)
    {
        SchemaBuilder::invariant(
            is_subclass_of($class, DataObject::class),
            '%s only accepts %s subclasses',
            static::class,
            DataObject::class
        );

        $this->dataObject = Injector::inst()->get($class);
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    public function hasField(string $fieldName): bool
    {
        return FieldAccessor::singleton()->hasField($this->dataObject, $fieldName);
    }

    /**
     * @param string $fieldName
     * @return string|null
     */
    public function getTypeForField(string $fieldName): ?string
    {
        $result = FieldAccessor::singleton()->accessField($this->dataObject, $fieldName);
        if (!$result) {
            return null;
        }

        return $result->config()->get('graphql_type');
    }

    /**
     * @return array
     */
    public function getDefaultFields(): array
    {
        return [
            'id' => 'ID',
        ];
    }

    /**
     * @return callable
     */
    public function getDefaultResolver(): callable
    {
        return [Resolver::class, 'resolve'];
    }

    /**
     * @return string
     */
    public function getSourceClass(): string
    {
        return get_class($this->dataObject);
    }

    /**
     * @param string $id
     * @param array|null $config
     * @return OperationCreator|null
     * @throws SchemaBuilderException
     */
    public function getOperationCreatorByIdentifier(string $id, ?array $config = null): ?OperationCreator
    {
        $registeredOperations = $this->config()->get('operations') ?? [];
        $creator = $registeredOperations[$id] ?? null;
        if (!$creator) {
            return null;
        }
        SchemaBuilder::invariant(
            class_exists($creator),
            'Operation creator %s does not exist'
        );
        /* @var OperationCreator $obj */
        $obj = Injector::inst()->get($creator);
        SchemaBuilder::invariant(
            $obj instanceof OperationCreator,
            'Operation %s is not an instance of %s',
            $creator,
            OperationCreator::class
        );

        return $obj;
    }

}
