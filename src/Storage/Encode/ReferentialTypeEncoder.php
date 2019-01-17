<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use SilverStripe\GraphQL\TypeAbstractions\InternalType;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeReference;

class ReferentialTypeEncoder implements TypeExpressionProvider
{

    /**
     * @var BuilderFactory
     */
    protected $factory;

    /**
     * @var NamedTypeFetcherInterface
     */
    protected $customTypeFetcher;

    /**
     * TypeSerialiser constructor.
     * @param BuilderFactory $factory
     * @param NamedTypeFetcherInterface $customTypeFetcher
     */
    public function __construct(BuilderFactory $factory, NamedTypeFetcherInterface $customTypeFetcher)
    {
        $this->factory = $factory;
        $this->customTypeFetcher = $customTypeFetcher;
    }
    /**
     * @param TypeAbstraction $type
     * @return Expr
     */
    public function getExpression(TypeAbstraction $type)
    {
        /* @var TypeReference $type */
        $namedTypeStr = $type->getName();
        if (InternalType::exists($namedTypeStr)) {
            $typeExpr = $this->factory->staticCall(Type::class, strtolower($namedTypeStr));
        } else {
            $typeExpr = $this->customTypeFetcher->getExpression($namedTypeStr);
        }
        if ($type->isList()) {
            $typeExpr = $this->factory->staticCall(Type::class, 'listOf', [$typeExpr]);
        }
        if ($type->isRequired()) {
            $typeExpr = $this->factory->staticCall(Type::class, 'nonNull', [$typeExpr]);
        }

        return $typeExpr;
    }
}