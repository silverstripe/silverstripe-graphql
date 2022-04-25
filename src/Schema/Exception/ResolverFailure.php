<?php


namespace SilverStripe\GraphQL\Schema\Exception;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;

/**
 * Captures a failure that happened within a resolver. Designed to add context
 * to the failure, as a chain of closures in a nested query can be very hard to debug.
 */
class ResolverFailure extends Exception
{
    public function __construct(
        callable $callable,
        array $resolverArgs,
        string $error
    ) {
        $args = $resolverArgs[1] ?? null;
        $info = $resolverArgs[3] ?? null;
        $message = sprintf(
            'Failed to resolve field %s returning %s.\n\n
            Got error: %s\n\n
            Path: %s\n\n
            Resolver %s failed in execution chain:\n\n
            %s\n\n
            Args: %s\n\n',
            $this->fieldName($info),
            $this->returnType($info),
            $error,
            $this->path($info),
            $this->resolver($callable),
            $this->executionChain($info),
            $this->args($args)
        );
        parent::__construct($message);
    }

    /**
     * @param ResolveInfo|null $info
     * @return ResolveInfo|mixed|string
     */
    private function fieldName(?ResolveInfo $info): string
    {
        return $info ? $info->fieldName : '(unknown)';
    }

    /**
     * @param ResolveInfo|null $info
     * @return string
     */
    private function returnType(?ResolveInfo $info): string
    {
        return $info ? $info->returnType : '(unknown)';
    }

    /**
     * @param callable $callable
     * @return string
     */
    private function resolver($callable): string
    {
        try {
            $ref = ResolverReference::create($callable);
            return $ref->toString();
        } catch (Exception $e) {
            return '(closure)';
        }
    }
    /**
     * @param ResolveInfo|null $info
     * @return string
     */
    private function executionChain(?ResolveInfo $info): string
    {
        if (!$info) {
            return '(unknown)';
        }
        $allCallables = $info->fieldDefinition->config['resolverComposition'] ?? [];
        $callables = array_map(function ($callable) {
            return var_export($callable, true);
        }, $allCallables ?? []);
        return implode("\n", $callables);
    }

    /**
     * @param ResolveInfo|null $info
     * @return string
     */
    private function path(?ResolveInfo $info): string
    {
        return $info ? implode('.', $info->path) : '(unknown)';
    }

    /**
     * @param array|null $args
     * @return string
     */
    private function args(?array $args): string
    {
        return $args ? json_encode($args, JSON_PRETTY_PRINT) : '(unknown)';
    }
}
