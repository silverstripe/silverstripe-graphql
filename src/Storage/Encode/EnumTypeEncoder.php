<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use SilverStripe\GraphQL\TypeAbstractions\EnumAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

class EnumTypeEncoder implements TypeEncoderInterface
{
    public function getExpression(TypeAbstraction $type)
    {
        /* @var EnumAbstraction $type */
        $items = Helpers::buildArrayItems($type->toArray(), ['values']);
        $items[] = new ArrayItem(
            Helpers::normaliseValue($type->getValues()),
            Helpers::normaliseValue('values')
        );

        return new New_(new FullyQualified(get_class($type)), [new Array_($items)]);

    }

    /**
     * @param Type $type
     * @return bool
     */
    public function appliesTo(TypeAbstraction $type)
    {
        return $type instanceof EnumAbstraction;
    }

}