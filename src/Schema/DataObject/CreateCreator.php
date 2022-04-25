<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Interfaces\InputTypeProvider;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Exception\PermissionsException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use Closure;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBEnum;

/**
 * Creates a "create" mutation for a DataObject
 */
class CreateCreator implements OperationCreator, InputTypeProvider
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
            $mutationName = 'create' . ucfirst($typeName ?? '');
        }
        $inputTypeName = self::inputTypeName($typeName);

        return ModelMutation::create($model, $mutationName)
            ->setType($typeName)
            ->setPlugins($plugins)
            ->setResolver([static::class, 'resolve'])
            ->setResolverContext([
                'dataClass' => $model->getSourceClass(),
            ])
            ->addArg('input', "{$inputTypeName}!");
    }

    /**
     * @param array $resolverContext
     * @return Closure
     */
    public static function resolve(array $resolverContext = []): Closure
    {
        $dataClass = $resolverContext['dataClass'] ?? null;
        return function ($obj, $args = [], $context = [], ResolveInfo $info = null) use ($dataClass) {
            if (!$dataClass) {
                return null;
            }
            $schema = SchemaConfigProvider::get($context);
            Schema::invariant(
                $schema,
                'Could not access schema in resolver for %s. Did you not add the %s context provider?',
                __CLASS__,
                SchemaConfigProvider::class
            );
            $singleton = Injector::inst()->get($dataClass);
            $member = UserContextProvider::get($context);
            if (!$singleton->canCreate($member, $context)) {
                throw new PermissionsException("Cannot create {$dataClass}");
            }

            /** @var DataObject $newObject */
            $newObject = Injector::inst()->create($dataClass);
            $update = [];
            foreach ($args['input'] as $fieldName => $value) {
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
            $newObject->update($update);

            // Save and return
            $newObject->write();
            $newObject = DataObject::get_by_id($dataClass, $newObject->ID);

            return $newObject;
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
            $type = $fieldObj->getNamedType();
            if (!$type) {
                continue;
            }
            $isScalar = Schema::isInternalType($type);
            if (!$isScalar && $fieldObj instanceof ModelField) {
                $dataClass = $fieldObj->getMetadata()->get('dataClass');
                $isScalar = $dataClass === DBEnum::class || is_subclass_of($dataClass, DBEnum::class);
            }

            if ($isScalar) {
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
     * @return CreateCreator
     */
    public function setFieldAccessor(FieldAccessor $fieldAccessor): self
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
        return 'Create' . ucfirst($typeName ?? '') . 'Input';
    }
}
