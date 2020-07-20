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
use SilverStripe\GraphQL\Schema\OperationCreator;
use SilverStripe\GraphQL\Schema\PermissionsException;
use SilverStripe\GraphQL\Schema\SchemaModelInterface;
use Closure;
use SilverStripe\ORM\DataObject;


class CreateCreator implements OperationCreator, InputTypeProvider
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
            'create' . ucfirst($typeName),
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
            $singleton = Injector::inst()->get($dataClass);
            if (!$singleton->canCreate($context['currentUser'], $context)) {
                throw new PermissionsException("Cannot create {$dataClass}");
            }

            $fieldAccessor = FieldAccessor::singleton();
            /** @var DataObject $newObject */
            $newObject = Injector::inst()->create($dataClass);
            $update = [];
            foreach ($args['Input'] as $fieldName => $value) {
                $update[$fieldAccessor->normaliseField($newObject, $fieldName)] = $value;
            }
            $newObject->update($update);

            // Save and return
            $newObject->write();
            $newObject = DataObject::get_by_id($dataClass, $newObject->ID);

            return $newObject;
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
     * @return CreateCreator
     */
    public function setFieldAccessor(FieldAccessor $fieldAccessor): CreateCreator
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
        return 'Create' . ucfirst($typeName) . 'Input';
    }

}
