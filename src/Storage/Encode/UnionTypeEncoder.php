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
        $factories = [
            'resolveTypeFactory' => 'resolveType',
            'typesFactory' => 'types'
        ];
        $items = Helpers::buildArrayItems(
            $type->config,
            array_merge(
                array_keys($factories),
                array_values($factories)
            )
        );
        foreach ($factories as $factoryName => $setting) {
            if ($type->config[$factoryName] instanceof ExpressionProvider) {
                /* @var ExpressionProvider $factory */
                $factory = $type->config[$factoryName];
                $items[] = new ArrayItem(
                    $factory->getExpression(),
                    Helpers::normaliseValue($setting)
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
            !$type->config['resolveType'] instanceof Closure ||
            (isset($type->config['resolveTypeFactory']) && $type->config['resolveTypeFactory'] instanceof RegistryAwareClosureFactory),
            'Cannot encode type %s with a closure for a "resolveType" property. Use callable array syntax, or a resolveTypeFactory setting'
        );

        Utils::invariant(
            !$type->config['types'] instanceof Closure ||
            (isset($type->config['typesFactory']) && $type->config['typesFactory'] instanceof RegistryAwareClosureFactory),
            'Cannot encode type %s with a closure for a "types" property. Use callable array syntax, or a typesFactory setting'
        );

    }
}