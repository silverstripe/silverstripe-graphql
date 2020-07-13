<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\FieldAbstraction;
use SilverStripe\GraphQL\Schema\OperationCreator;
use SilverStripe\GraphQL\Schema\QueryAbstraction;
use SilverStripe\GraphQL\Schema\SchemaModelInterface;
use SilverStripe\GraphQL\Schema\SchemaUtils;
use SilverStripe\ORM\DataList;
use \Closure;

class ReadCreator implements OperationCreator
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
            'read' . ucfirst(SchemaUtils::pluralise($typeName)),
            [
                'type' => sprintf('[%s]', $typeName),
                'defaultResolver' => [static::class, 'resolve'],
                'resolverContext' => [
                    'dataClass' => $model->getSourceClass()
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
        return static function () use ($dataClass) {
            if (!$dataClass) {
                return null;
            }
            return DataList::create($dataClass);
        };
    }

}
