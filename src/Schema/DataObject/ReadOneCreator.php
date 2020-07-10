<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use SilverStripe\GraphQL\Schema\FieldAbstraction;
use SilverStripe\GraphQL\Schema\InputTypeProvider;
use SilverStripe\GraphQL\Schema\OperationCreator;
use SilverStripe\GraphQL\Schema\QueryAbstraction;
use SilverStripe\GraphQL\Schema\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\TypeAbstraction;
use SilverStripe\ORM\DataObject;
use \Closure;

class ReadOneCreator implements OperationCreator
{
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        ?array $config = null
    ): FieldAbstraction
    {
        return QueryAbstraction::create(
            'readOne' . ucfirst($typeName),
            [
                'type' => $typeName,
                'defaultResolver' => [static::class, 'resolve'],
                'resolverContext' => [$model->getSourceClass()],
                'args' => [
                    'ID' => 'ID!',
                ]
            ]
        );
    }

    /**
     * @param string $dataClass
     * @return Closure
     */
    public static function resolve(string $dataClass): Closure
    {
        return function ($obj, $args = []) use ($dataClass) {
            return DataObject::get_by_id($dataClass, $args['ID']);
        };
    }

}
