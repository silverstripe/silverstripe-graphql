<?php

namespace SilverStripe\GraphQL\GraphQLPHP\Encoders;

use GraphQL\Type\Definition\Type;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use SilverStripe\GraphQL\Schema\Components\InternalType;
use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\GraphQL\Schema\Components\TypeReference;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\NamedTypeFetcherInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeExpressionProvider;

class ReferentialTypeEncoder implements TypeExpressionProvider
{

    /**
     * @var BuilderFactory
     */
    protected $factory;

    /**
     * @var \SilverStripe\GraphQL\Schema\Encoding\Interfaces\NamedTypeFetcherInterface
     */
    protected $customTypeFetcher;

    /**
     * @param BuilderFactory $factory
     * @param \SilverStripe\GraphQL\Schema\Encoding\Interfaces\NamedTypeFetcherInterface $customTypeFetcher
     */
    public function __construct(BuilderFactory $factory, NamedTypeFetcherInterface $customTypeFetcher)
    {
        $this->factory = $factory;
        $this->customTypeFetcher = $customTypeFetcher;
    }

    /**
     * @param AbstractType $type
     * @return Expr
     */
    public function getExpression(AbstractType $type)
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