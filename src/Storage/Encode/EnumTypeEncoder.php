<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;

class EnumTypeEncoder implements TypeEncoderInterface
{
    public function getExpression(Type $type)
    {
        /* @var EnumType $type */
        $items = Helpers::buildArrayItems($type->config, ['values']);
        $items[] = new ArrayItem(
            Helpers::normaliseValue($type->getValues()),
            Helpers::normaliseValue('values')
        );

        return new New_(new FullyQualified(get_class($type)), [new Array_($items)]);

    }

    /**
     * @param Type $type
     * @return string
     */
    public function getName(Type $type)
    {
        return $type->config['name'];
    }

    /**
     * @param Type $type
     * @return bool
     */
    public function appliesTo(Type $type)
    {
        return $type instanceof EnumType;
    }

    /**
     * @param Type $type
     * @throws \GraphQL\Error\Error
     */
    public function assertValid(Type $type)
    {
        Utils::invariant(
            !$type->astNode,
            'Type "%s" has ASTNodes assigned and cannot be serialised.',
            $type->name
        );

    }
}