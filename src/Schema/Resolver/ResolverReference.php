<?php


namespace SilverStripe\GraphQL\Schema\Resolver;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * A uniform way of referring to a resolver callable. Normalises the string/array variants
 */
class ResolverReference
{
    use Injectable;

    private $class;

    private $method;

    /**
     * @param string|array $callable
     * @throws SchemaBuilderException
     */
    public function __construct($callable)
    {
        Schema::invariant(
            is_array($callable) || (is_string($callable) && stristr($callable ?? '', '::') !== false),
            '%s accepts a valid callable in array or string form',
            __CLASS__
        );
        $callableArray = is_string($callable) ? explode('::', $callable) : $callable;
        Schema::invariant(
            is_callable($callableArray),
            'Callable %s provided to %s is not valid',
            var_export($callable, true),
            __CLASS__
        );

        list($class, $method) = $callableArray;
        $this->class = $class;
        $this->method = $method;
    }

    public function toArray(): array
    {
        return [$this->class, $this->method];
    }

    public function toString(): string
    {
        return sprintf('%s::%s', $this->class, $this->method);
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
