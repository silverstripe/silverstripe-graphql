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

class UnionTypeEncoder implements TypeEncoderInterface
{
    /**
     * @param Type $type
     * @return \PhpParser\Node\Expr|New_
     */
    public function getExpression(Type $type)
    {
        $excludes = ['types', 'resolveType'];
        $items = Helpers::buildArrayItems($type->config, $excludes);
        foreach ($excludes as $key) {
            if ($type->config[$key] instanceof ResolverFactory) {
                /* @var ResolverFactory $factory */
                $factory = $type->config[$key];
                $items[] = new ArrayItem(
                    $factory->getExpression(),
                    Helpers::normaliseValue($key)
                );
            }
        }

        return new New_(
            new FullyQualified(get_class($type)),
            [
                new Array_($items)
            ]
        );
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
        return $type instanceof UnionType;
    }

    /**
     * @param Type $type
     * @throws \GraphQL\Error\Error
     */
    public function assertValid(Type $type)
    {
        Utils::invariant(
            !$type->astNode,
            'Cannot encode type %s with an ASTNode assigned',
            $type->name
        );

        Utils::invariant(
            !$type->config['resolveType'] instanceof Closure,
            'Cannot encode type %s with a closure for a "types" property. Use callable array syntax instead.'
        );
    }
}