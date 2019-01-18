<?php

namespace SilverStripe\GraphQL\GraphQLPHP\Encoders;

use GraphQL\Type\Definition\EnumType;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use SilverStripe\GraphQL\Schema\Components\Enum;
use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\GraphQL\Schema\Encoding\Helpers;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeEncoderInterface;

class EnumTypeEncoder implements TypeEncoderInterface
{
    public function getExpression(AbstractType $type)
    {
        /* @var Enum $type */
        $items = Helpers::buildArrayItems($type->toArray(), ['values']);
        $items[] = new ArrayItem(
            Helpers::normaliseValue($type->getValues()),
            Helpers::normaliseValue('values')
        );

        return new New_(new FullyQualified(EnumType::class), [new Array_($items)]);

    }

    /**
     * @param AbstractType $type
     * @return bool
     */
    public function appliesTo(AbstractType $type)
    {
        return $type instanceof Enum;
    }

}