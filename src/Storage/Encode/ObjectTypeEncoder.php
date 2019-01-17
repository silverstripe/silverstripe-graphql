<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Closure as ClosureExpression;
use SilverStripe\GraphQL\TypeAbstractions\ArgumentAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\DynamicResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\InputTypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\ObjectTypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\StaticResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;
use InvalidArgumentException;

class ObjectTypeEncoder implements TypeEncoderInterface
{
    /**
     * @var TypeExpressionProvider
     */
    protected $referentialTypeEncoder;

    /**
     * @var ResolverEncoderRegistryInterface
     */
    protected $encoderRegistry;

    /**
     * ObjectTypeEncoder constructor.
     * @param TypeExpressionProvider $referentialTypeEncoder
     * @param ResolverEncoderRegistryInterface $encoderRegistry
     */
    public function __construct(
        TypeExpressionProvider $referentialTypeEncoder,
        ResolverEncoderRegistryInterface $encoderRegistry
    ) {
        $this->referentialTypeEncoder = $referentialTypeEncoder;
        $this->encoderRegistry = $encoderRegistry;
    }

    /**
     * @param TypeAbstraction $type
     * @return Expr
     */
    public function getExpression(TypeAbstraction $type)
    {
        $items = Helpers::buildArrayItems($type->toArray(), ['fields']);
        $items[] = new ArrayItem(
            $this->buildFieldsExpression($type->getFields()),
            Helpers::normaliseValue('fields')
        );
        return new New_(new FullyQualified(get_class($type)), [new Array_($items)]);
    }

    /**
     * @param TypeAbstraction $type
     * @return bool
     */
    public function appliesTo(TypeAbstraction $type)
    {
        return $type instanceof ObjectTypeAbstraction || $type instanceof InputTypeAbstraction;
    }

    /**
     * @param FieldAbstraction[] $fields
     * @return ClosureExpression
     */
    protected function buildFieldsExpression(array $fields)
    {
        $items = array_map(function ($field) {
            /* @var FieldAbstraction $field */
            $fieldItems = Helpers::buildArrayItems(
                $field->toArray(),
                ['type', 'args', 'resolve', 'resolverFactory']
            );
            $fieldItems[] = new ArrayItem(
                $this->referentialTypeEncoder->getExpression($field->getType()),
                Helpers::normaliseValue('type')
            );
            $resolverAbstract = $field->getResolver();
            if ($resolverAbstract) {
                /* @var ResolverEncoderInterface $encoder */
                $encoder = $this->encoderRegistry->getEncoderForResolver($resolverAbstract);
                $fieldItems[] = new ArrayItem(
                    $encoder->getExpression($resolverAbstract),
                    Helpers::normaliseValue('resolve')
                );
            }

            // args
            $args = array_map(function ($arg) {
                /* @var ArgumentAbstraction $arg */
                $argItems = Helpers::buildArrayItems($arg->toArray(), ['type']);
                $argItems[] = new ArrayItem(
                    $this->referentialTypeEncoder->getExpression($arg->getType()),
                    Helpers::normaliseValue('type')
                );
                return new ArrayItem(new Array_($argItems));
            }, $field->getArgs());

            $fieldItems[] = new ArrayItem(
                new Array_($args),
                Helpers::normaliseValue('args')
            );

            return new ArrayItem(new Array_($fieldItems));

        }, $fields);

        return new ClosureExpression([
            'stmts' => [
                new Return_(new Array_($items))
            ]
        ]);
    }
    
}