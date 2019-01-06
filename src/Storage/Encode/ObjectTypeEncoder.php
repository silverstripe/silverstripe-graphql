<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\FieldDefinition;
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
use PhpParser\Node\Expr\Closure as ClosureExpression;

class ObjectTypeEncoder implements TypeEncoderInterface
{
    /**
     * @var TypeEncoderInterface
     */
    protected $referentialTypeEncoder;

    public function __construct(TypeEncoderInterface $referentialTypeEncoder)
    {
        $this->typeEncoder = $referentialTypeEncoder;
    }

    /**
     * @param Type $type
     * @return Expr
     */
    public function getExpression(Type $type)
    {
        $items = Helpers::buildArrayItems($type->config, ['fields']);
        $items[] = new ArrayItem(
            $this->buildFieldsExpression($type->getFields()),
            Helpers::normaliseValue('fields')
        );
        return new New_(new FullyQualified(get_class($type)), [new Array_($items)]);
    }

    /**
     * @param Type $type
     * @return string
     */
    public function getName(Type $type)
    {
        return $type->name;
    }

    /**
     * @param Type $type
     * @return bool
     */
    public function appliesTo(Type $type)
    {
        return $type instanceof ObjectType || $type instanceof InputObjectType;
    }

    /**
     * @param FieldDefinition[] $fields
     * @return ClosureExpression
     */
    protected function buildFieldsExpression(array $fields)
    {
        $items = array_map(function ($field) {
            /* @var FieldDefinition $field */
            $this->assertFieldValid($field);
            $fieldItems = Helpers::buildArrayItems($field->config, ['type', 'args', 'resolve']);

            // type
            $fieldItems[] = new ArrayItem(
                $this->referentialTypeEncoder->getExpression($field->getType()),
                Helpers::normaliseValue('type')
            );

            // resolve
            /* @var ResolverFactory|callable $resolver */
            $resolver = $field->config['resolve'];
            $fieldItems[] = new ArrayItem(
                Helpers::normaliseValue('resolve'),
                $resolver instanceof ResolverFactory
                    ? $resolver->getExpression()
                    : Helpers::normaliseValue($resolver)
            );

            // args
            $args = array_map(function ($arg) {
                /* @var FieldArgument $arg */
                $this->assertArgValid($arg);
                $argItems = Helpers::buildArrayItems($arg->config, ['type']);
                $argItems[] = new ArrayItem(
                    $this->referentialTypeEncoder->getExpression($arg->getType()),
                    Helpers::normaliseValue('type')
                );
                return new ArrayItem(new Array_($argItems));
            }, $field->args);

            $fieldItems[] = new ArrayItem(
                new Array_($args),
                Helpers::normaliseValue('args')
            );

            return new ArrayItem(new Array_($fieldItems));

        }, $fields);

        return new ClosureExpression(
            new Return_(
                new Array_($items)
            )
        );
    }
    /**
     * @param Type $type
     * @throws Error
     */
    public function assertValid(Type $type)
    {
        Utils::invariant(
            !$type->astNode && empty($type->extensionASTNodes),
            'Type "%s" has ASTNodes assigned and cannot be serialised.',
            $type->name
        );
        Utils::invariant(
            !isset($type->config['isTypeOf']) || !$type->config['isTypeOf'] instanceof Closure,
            'Type "%s" is using a closure for the isTypeOf property and cannot be serialised.',
            $type->name
        );
        Utils::invariant(
            !$type->resolveFieldFn || !$type->resolveFieldFn instanceof Closure,
            'Type "%s" is using a closure for the resolveField property and cannot be serialised.',
            $type->name
        );

    }

    /**
     * @param FieldDefinition $field
     * @throws Error
     */
    protected function assertFieldValid(FieldDefinition $field)
    {
        Utils::invariant(
            !$field->resolveFn instanceof Closure,
            'Resolver function for field "%s" cannot be a closure. Use callable array syntax instead.',
            $field->name
        );

        Utils::invariant(
            !$field->mapFn instanceof Closure,
            'Map function for field "%s" cannot be a closure. Use callable array syntax instead.',
            $field->name
        );

        Utils::invariant(
            !$field->complexityFn instanceof Closure,
            'Complexity function for field "%s" cannot be a closure. Use callable array syntax instead.',
            $field->name
        );

        Utils::invariant(
            !$field->astNode,
            'Cannot encode field "%s" that has ASTNode property assigned',
            $field->name
        );
    }

    /**
     * @param FieldArgument $arg
     * @throws Error
     */
    protected function assertArgValid(FieldArgument $arg)
    {
        Utils::invariant(
            !$arg->astNode,
            'Field argument %s cannot be encoded because it has an astNode property assigned',
            $arg->name
        );
    }

}