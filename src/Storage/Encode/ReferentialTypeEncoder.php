<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use SilverStripe\GraphQL\TypeAbstractions\InternalType;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

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
        $namedTypeStr = $type->getName();
        if (InternalType::exists($namedTypeStr)) {
            $type = $this->factory->staticCall(Type::class, strtolower($namedTypeStr));
        } else {
            $type = $this->customTypeFetcher->getExpression($namedTypeStr);
        }
        if ($type->isList()) {
            $type = $this->factory->staticCall(Type::class, 'listOf', [$namedType]);
        }
        if ($type->isRequired()) {
            $type = $this->factory->staticCall(Type::class, 'nonNull', [$namedType]);
        }

        return $type;
    }
}