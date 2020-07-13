<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\FieldAbstraction;
use SilverStripe\GraphQL\Schema\OperationCreator;
use SilverStripe\GraphQL\Schema\QueryAbstraction;
use SilverStripe\GraphQL\Schema\SchemaModelInterface;
use SilverStripe\ORM\DataObject;
use \Closure;

class ReadOneCreator implements OperationCreator
{
    use Injectable;

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
        return QueryAbstraction::create(
            'readOne' . ucfirst($typeName),
            [
                'type' => $typeName,
                'defaultResolver' => [static::class, 'resolve'],
                'resolverContext' => [
                    'dataClass' => $model->getSourceClass()
                ],
                'args' => [
                    'ID' => 'ID!',
                ]
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
        return static function ($obj, $args = []) use ($dataClass) {
            if (!$dataClass) {
                return null;
            }
            return DataObject::get_by_id($dataClass, $args['ID']);
        };
    }

}
