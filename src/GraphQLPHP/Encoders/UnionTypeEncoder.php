<?php

namespace SilverStripe\GraphQL\GraphQLPHP\Encoders;

use GraphQL\Type\Definition\UnionType;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\GraphQL\Schema\Components\Union;
use SilverStripe\GraphQL\Schema\Encoding\Helpers;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ResolverEncoderRegistryInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeEncoderInterface;

class UnionTypeEncoder implements TypeEncoderInterface
{
    /**
     * @var ResolverEncoderRegistryInterface
     */
    protected $encoderRegistry;

    /**
     * UnionTypeEncoder constructor.
     * @param ResolverEncoderRegistryInterface $encoderRegistry
     */
    public function __construct(ResolverEncoderRegistryInterface $encoderRegistry)
    {
        $this->encoderRegistry = $encoderRegistry;
    }

    /**
     * @param AbstractType $type
     * @return \PhpParser\Node\Expr|New_
     */
    public function getExpression(AbstractType $type)
    {
        /* @var Union $type */
        $items = Helpers::buildArrayItems(
            $type->toArray(),
            ['resolveType', 'types']
        );
        $typeFactory = $type->getTypeFactory();
        $resolveTypeFactory = $type->getResolveTypeFactory();
        if ($typeFactory) {
            $encoder = $this->encoderRegistry->getEncoderForResolver($typeFactory);
            $items[] = new ArrayItem(
                $encoder->getExpression($typeFactory),
                Helpers::normaliseValue('types')
            );
        }
        if ($resolveTypeFactory) {
            $encoder = $this->encoderRegistry->getEncoderForResolver($resolveTypeFactory);
            $items[] = new ArrayItem(
                $encoder->getExpression($resolveTypeFactory),
                Helpers::normaliseValue('resolveType')
            );
        }

        return new New_(
            new FullyQualified(UnionType::class),
            [
                new Array_($items)
            ]
        );
    }

    /**
     * @param AbstractType $type
     * @return bool
     */
    public function appliesTo(AbstractType $type)
    {
        return $type instanceof Union;
    }
}
