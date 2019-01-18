<?php

namespace SilverStripe\GraphQL\GraphQLPHP\Encoders;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Expr\Closure as ClosureExpression;
use SilverStripe\GraphQL\Schema\Components\Argument;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\Input;
use SilverStripe\GraphQL\Schema\Components\FieldCollection;
use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\GraphQL\Schema\Encoding\Helpers;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ResolverEncoderRegistryInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeEncoderInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeExpressionProvider;

class ObjectTypeEncoder implements TypeEncoderInterface
{
    /**
     * @var \SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeExpressionProvider
     */
    protected $referentialTypeEncoder;

    /**
     * @var ResolverEncoderRegistryInterface
     */
    protected $encoderRegistry;

    /**
     * ObjectTypeEncoder constructor.
     * @param \SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeExpressionProvider $referentialTypeEncoder
     * @param \SilverStripe\GraphQL\Schema\Encoding\Interfaces\ResolverEncoderRegistryInterface $encoderRegistry
     */
    public function __construct(
        TypeExpressionProvider $referentialTypeEncoder,
        ResolverEncoderRegistryInterface $encoderRegistry
    ) {
        $this->referentialTypeEncoder = $referentialTypeEncoder;
        $this->encoderRegistry = $encoderRegistry;
    }

    /**
     * @param AbstractType $type
     * @return Expr
     */
    public function getExpression(AbstractType $type)
    {
        /* @var FieldCollection|Input $type */
        $items = Helpers::buildArrayItems($type->toArray(), ['fields']);
        $items[] = new ArrayItem(
            $this->buildFieldsExpression($type->getFields()),
            Helpers::normaliseValue('fields')
        );
        $class = $type instanceof Input ? InputObjectType::class : ObjectType::class;
        return new New_(new FullyQualified($class), [new Array_($items)]);
    }

    /**
     * @param AbstractType $type
     * @return bool
     */
    public function appliesTo(AbstractType $type)
    {
        return $type instanceof FieldCollection || $type instanceof Input;
    }

    /**
     * @param Field[] $fields
     * @return ClosureExpression
     */
    protected function buildFieldsExpression(array $fields)
    {
        $items = array_map(function ($field) {
            /* @var Field $field */
            $fieldItems = Helpers::buildArrayItems(
                $field->toArray(),
                ['type', 'args', 'resolver']
            );
            $fieldItems[] = new ArrayItem(
                $this->referentialTypeEncoder->getExpression($field->getType()),
                Helpers::normaliseValue('type')
            );
            $resolverAbstract = $field->getResolver();
            if ($resolverAbstract) {
                /* @var \SilverStripe\GraphQL\Schema\Encoding\Interfaces\ResolverEncoderInterface $encoder */
                $encoder = $this->encoderRegistry->getEncoderForResolver($resolverAbstract);
                $fieldItems[] = new ArrayItem(
                    $encoder->getExpression($resolverAbstract),
                    Helpers::normaliseValue('resolve')
                );
            }

            // args
            $args = array_map(function ($arg) {
                /* @var Argument $arg */
                $argItems = Helpers::buildArrayItems($arg->toArray(), ['type']);
                $argItems[] = new ArrayItem(
                    $this->referentialTypeEncoder->getExpression($arg->getType()),
                    Helpers::normaliseValue('type')
                );
                return new ArrayItem(new Array_($argItems));
            }, $field->getArgs());
            if (!empty($args)) {
                $fieldItems[] = new ArrayItem(
                    new Array_($args),
                    Helpers::normaliseValue('args')
                );
            }

            return new ArrayItem(new Array_($fieldItems));

        }, $fields);

        return new ClosureExpression([
            'stmts' => [
                new Return_(new Array_($items))
            ]
        ]);
    }
    
}