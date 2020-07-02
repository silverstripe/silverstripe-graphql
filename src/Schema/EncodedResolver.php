<?php


namespace SilverStripe\GraphQL\Schema;


use SilverStripe\View\ViewableData;

class EncodedResolver extends ViewableData implements Encoder
{
    /**
     * EncodedResolver constructor.
     * @param string|array $resolverFunc
     * @throws SchemaBuilderException
     */
    public function __construct($resolverFunc)
    {
        parent::__construct();
        SchemaBuilder::invariant(
            (is_array($resolverFunc) || is_string($resolverFunc)) && is_callable($resolverFunc),
            '%s not passed a valid callable',
            __CLASS__
        );
        $this->resolverFunc = $resolverFunc;
    }

    /**
     * @return string
     */
    public function encode(): string
    {
        if (is_string($this->resolverFunc)) {
            return sprintf('%s', $this->resolverFunc);
        }

        list($class, $method) = $this->resolverFunc;

        return sprintf("['%s', '%s']", $class, $method);
    }

    /**
     * @return string
     */
    public function forTemplate(): string
    {
        return $this->encode();
    }

}
