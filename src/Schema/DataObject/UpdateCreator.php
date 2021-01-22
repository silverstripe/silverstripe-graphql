<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\SchemaContextProvider;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Interfaces\InputTypeProvider;
use SilverStripe\GraphQL\Schema\Exception\MutationException;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Exception\PermissionsException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataList;
use Closure;

/**
 * Creates an update operation for a DataObject
 */
class UpdateCreator implements OperationCreator, InputTypeProvider
{
    use Configurable;
    use Injectable;
    use FieldReconciler;

    private static $dependencies = [
        'FieldAccessor' => '%$' . FieldAccessor::class,
    ];

    /**
     * @var FieldAccessor
     */
    private $fieldAccessor;

    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array $config
     * @return ModelOperation|null
     * @throws SchemaBuilderException
     */
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): ?ModelOperation {
        $plugins = $config['plugins'] ?? [];
        $mutationName = $config['name'] ?? null;
        if (!$mutationName) {
            $mutationName = 'update' . ucfirst($typeName);
        }
        $inputTypeName = self::inputTypeName($typeName);

        return ModelMutation::create($model, $mutationName)
            ->setType($typeName)
            ->setPlugins($plugins)
            ->setResolver([static::class, 'resolve'])
            ->addResolverContext('dataClass', $model->getSourceClass())
            ->addArg('input', "{$inputTypeName}!");
    }

    /**
     * @param array $resolverContext
     * @return Closure
     */
    public static function resolve(array $resolverContext = []): Closure
    {
        $dataClass = $resolverContext['dataClass'] ?? null;
        return function ($obj, array $args, array $context, ResolveInfo $info) use ($dataClass) {
            if (!$dataClass) {
                return null;
            }
            $schema = SchemaContextProvider::get($context);
            Schema::invariant(
                $schema,
                'Could not access schema in resolver for %s. Did you not add the %s context provider?',
                __CLASS__,
                SchemaContextProvider::class
            );
            $fieldName = FieldAccessor::formatField('ID');
            $input = $args['input'];
            $obj = DataList::create($dataClass)
                ->byID($input[$fieldName]);
            if (!$obj) {
                throw new MutationException(sprintf(
                    '%s with ID %s not found',
                    $dataClass,
                    $input[$fieldName]
                ));
            }
            unset($input[$fieldName]);
            $member = UserContextProvider::get($context);
            if (!$obj->canEdit($member)) {
                throw new PermissionsException(sprintf(
                    'Cannot edit this %s',
                    $dataClass
                ));
            }
            $update = [];
            foreach ($input as $fieldName => $value) {
                $info = $schema->mapFieldByClassName($dataClass, $fieldName);
                Schema::invariant(
                    $info,
                    'Count not map field %s on %s in resolver for %s',
                    $fieldName,
                    $dataClass,
                    __CLASS__
                );
                list ($type, $property) = $info;
                $update[$property] = $value;
            }

            $obj->update($update);
            $obj->write();

            return $obj;
        };
    }

    /**
     * @param ModelType $modelType
     * @param array $config
     * @return array
     * @throws SchemaBuilderException
     */
    public function provideInputTypes(ModelType $modelType, array $config = []): array
    {
        $includedFields = $this->reconcileFields($config, $modelType);

        $fieldMap = [];
        foreach ($includedFields as $fieldName) {
            $fieldObj = $modelType->getFieldByName($fieldName);
            if (!$fieldObj) {
                continue;
            }
            $type = $fieldObj->getType();
            // No nested input types... yet
            if ($type && Schema::isInternalType($type)) {
                $fieldMap[$fieldName] = $type;
            }
        }
        $inputType = InputType::create(
            self::inputTypeName($modelType->getName()),
            [
                'fields' => $fieldMap
            ]
        );

        return [$inputType];
    }

    /**
     * @return FieldAccessor
     */
    public function getFieldAccessor(): FieldAccessor
    {
        return $this->fieldAccessor;
    }

    /**
     * @param FieldAccessor $fieldAccessor
     * @return UpdateCreator
     */
    public function setFieldAccessor(FieldAccessor $fieldAccessor): UpdateCreator
    {
        $this->fieldAccessor = $fieldAccessor;
        return $this;
    }


    /**
     * @param string $typeName
     * @return string
     */
    private static function inputTypeName(string $typeName): string
    {
        return 'Update' . ucfirst($typeName) . 'Input';
    }
}
