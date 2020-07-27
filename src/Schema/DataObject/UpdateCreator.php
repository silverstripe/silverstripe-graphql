<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelOperation;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Interfaces\InputTypeProvider;
use SilverStripe\GraphQL\Schema\Field\Mutation;
use SilverStripe\GraphQL\Schema\Exception\MutationException;
use SilverStripe\GraphQL\Schema\Interfaces\OperationCreator;
use SilverStripe\GraphQL\Schema\Exception\PermissionsException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaModelInterface;
use SilverStripe\ORM\DataList;
use Closure;


class UpdateCreator implements OperationCreator, InputTypeProvider
{
    use Configurable;
    use Injectable;

    private static $dependencies = [
        'FieldAccessor' => '%$' . FieldAccessor::class,
    ];

    /**
     * @var array
     * @config
     */
    private static $default_plugins = [];

    /**
     * @var FieldAccessor
     */
    private $fieldAccessor;

    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array $config
     * @return ModelOperation
     * @throws SchemaBuilderException
     */
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): ModelOperation
    {
        $defaultPlugins = $this->config()->get('default_plugins');
        $configPlugins = $config['plugins'] ?? [];
        $plugins = array_merge($defaultPlugins, $configPlugins);
        $mutationName = 'update' . ucfirst($typeName);
        $inputTypeName = self::inputTypeName($typeName);

        return ModelMutation::create($model, $mutationName)
            ->setType($typeName)
            ->setPlugins($plugins)
            ->setDefaultResolver([static::class, 'resolve'])
            ->addArg('Input', "{$inputTypeName}!");
    }

    /**
     * @param array $resolverContext
     * @return Closure
     */
    public static function resolve(array $resolverContext = []): Closure
    {
        $dataClass = $resolverContext['dataClass'] ?? null;
        return static function ($obj, array $args, array $context, ResolveInfo $info) use ($dataClass) {
            if (!$dataClass) {
                return null;
            }
            $input = $args['Input'];
            $obj = DataList::create($dataClass)
                ->byID($input['id']);
            if (!$obj) {
                throw new MutationException(sprintf(
                    '%s with ID %s not found',
                    $dataClass,
                    $input['id']
                ));
            }
            unset($input['id']);
            if (!$obj->canEdit($context['currentUser'])) {
                throw new PermissionsException(sprintf(
                    'Cannot edit this %s',
                    $dataClass
                ));
            }
            $fieldAccessor = FieldAccessor::singleton();
            $update = [];
            foreach ($input as $fieldName => $value) {
                $update[$fieldAccessor->normaliseField($obj, $fieldName)] = $value;
            }

            $obj->update($update);
            $obj->write();

            return $obj;
        };
    }

    public function provideInputTypes(SchemaModelInterface $model, string $typeName, array $config = []): array
    {
        $dataObject = Injector::inst()->get($model->getSourceClass());
        $allFields = $this->getFieldAccessor()->getAllFields($dataObject, false);
        $excluded = $config['exclude'] ?? [];
        $includedFields = array_diff($allFields, $excluded);
        $fieldMap = [];
        foreach ($includedFields as $fieldName) {
            $fieldMap[$fieldName] = $model->getTypeForField($fieldName);
        }
        $inputType = InputType::create(
            self::inputTypeName($typeName),
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
