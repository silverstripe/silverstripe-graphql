<?php


namespace SilverStripe\GraphQL\Schema\Components;

/**
 * Represents an exportable execution context.
 * Each implementation needs an equivalent
 * {@link \SilverStripe\GraphQL\Schema\Encoding\Interfaces\FunctionEncoderInterface},
 * which is able to transform this context into a PHP expression which can be persisted.
 *
 * It can't be replaced by a closure directly,
 * because their closed over context can't be persisted
 * as generated code. This mechanism is complicating GraphQL definitions,
 * but is an essential part of making the system performant through generated code.
 */
abstract class AbstractFunction
{
    /**
     * @return mixed
     */
    abstract public function export();
}
