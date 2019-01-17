<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Utils\Utils;
use Closure;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\UnionTypeAbstraction;

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
     * @param TypeAbstraction $type
     * @return \PhpParser\Node\Expr|New_
     */
    public function getExpression(TypeAbstraction $type)
    {   
        /* @var UnionTypeAbstraction $type */
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
     * @param TypeAbstraction $type
     * @return bool
     */
    public function appliesTo(TypeAbstraction $type)
    {
        return $type instanceof UnionTypeAbstraction;
    }

}