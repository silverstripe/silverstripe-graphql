<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\GraphQL\Schema\FieldAbstraction;
use SilverStripe\GraphQL\Schema\OperationCreator;
use SilverStripe\GraphQL\Schema\QueryAbstraction;
use SilverStripe\GraphQL\Schema\SchemaModelInterface;
use SilverStripe\ORM\DataList;
use \Closure;

class ReadCreator implements OperationCreator
{
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        ?array $config = null
    ): FieldAbstraction
    {
        return QueryAbstraction::create(
            'read' . ucfirst(static::pluralise($typeName)),
            [
                'type' => sprintf('[%s]', $typeName),
                'defaultResolver' => [static::class, 'resolve'],
                'resolverContext' => [$model->getSourceClass()]
            ]
        );
    }

    /**
     * @param string $dataClass
     * @return Closure
     */
    public static function resolve(string $dataClass): Closure
    {
        return function () use ($dataClass) {
            return DataList::create($dataClass);
        };
    }

    /**
     * Pluralises a word if quantity is not one.
     *
     * @param string $singular Singular form of word
     * @return string Pluralised word if quantity is not one, otherwise singular
     */
    public static function pluralise(string $singular): string {
        $last_letter = strtolower($singular[strlen($singular)-1]);
        switch($last_letter) {
            case 'y':
                return substr($singular, 0, -1) . 'ies';
            case 's':
                return $singular . 'es';
            default:
                return $singular . 's';
        }
    }

}
