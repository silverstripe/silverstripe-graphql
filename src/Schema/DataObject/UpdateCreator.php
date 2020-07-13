<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\FieldAbstraction;
use SilverStripe\GraphQL\Schema\InputTypeAbstraction;
use SilverStripe\GraphQL\Schema\InputTypeProvider;
use SilverStripe\GraphQL\Schema\MutationAbstraction;
use SilverStripe\GraphQL\Schema\MutationException;
use SilverStripe\GraphQL\Schema\OperationCreator;
use SilverStripe\GraphQL\Schema\PermissionsException;
use SilverStripe\GraphQL\Schema\SchemaModelInterface;
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
     * @var FieldAccessor
     */
    private $fieldAccessor;

    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array $config
     * @return FieldAbstraction
     */
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): FieldAbstraction
    {
        return MutationAbstraction::create(
            'update' . ucfirst($typeName),
            [
                'type' => $typeName,
                'defaultResolver' => [static::class, 'resolve'],
                'resolverContext' => [
                    'dataClass' => $model->getSourceClass()
                ],
                'args' => [
                    'Input' => self::inputTypeName($typeName) . '!',
                ],
            ]
        );
    }

    /**
     * @param array $resolverContext
     * @return Closure
     */
    public static function resolve(array $resolverContext = []): Closure
    {
        $dataClass = $resolverContext['dataClass'] ?? null;
        return static function ($obj, $args = [], $context = [], ResolveInfo $info) use ($dataClass) {
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

            $obj->update($input);
            $obj->write();

            return $obj;
        };
    }

    public function provideInputTypes(SchemaModelInterface $model, string $typeName, array $config = []): array
    {
        $dataObject = Injector::inst()->get($model->getSourceClass());
        $allFields = $this->getFieldAccessor()->getAllFields($dataObject);
        $excluded = $config['exclude'] ?? [];
        $includedFields = array_diff($allFields, $excluded);
        $fieldMap = [];
        foreach ($includedFields as $fieldName) {
            $fieldMap[$fieldName] = $model->getTypeForField($fieldName);
        }
        $inputType = InputTypeAbstraction::create(
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
