<?php


namespace SilverStripe\GraphQL\Schema\Resolver;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\Encoder;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\View\ViewableData;

class EncodedResolver extends ViewableData implements Encoder
{
    /**
     * @var array|string
     */
    private $resolverFunc;

    /**
     * @var array
     */
    private $context = [];

    /**
     * EncodedResolver constructor.
     * @param string|array $resolverFunc
     * @param array $context
     * @throws SchemaBuilderException
     */
    public function __construct($resolverFunc, array $context = [])
    {
        parent::__construct();
        Schema::invariant(
            (is_array($resolverFunc) || is_string($resolverFunc)) && is_callable($resolverFunc),
            '%s not passed a valid callable',
            __CLASS__
        );
        $this->resolverFunc = $resolverFunc;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function encode(): string
    {
        if ($this->context) {
            return $this->renderWith(__NAMESPACE__ . '\\ContextResolver');
        }

        return $this->getCallable();
    }

    /**
     * @return string
     */
    public function getCallable(): string
    {
        if (is_string($this->resolverFunc)) {
            return sprintf('%s', $this->resolverFunc);
        }

        list($class, $method) = $this->resolverFunc;

        return sprintf("['%s', '%s']", $class, $method);
    }

    /**
     * @return string|null
     */
    public function getContextArgs(): ?string
    {
        return !empty($this->getContext()) ? var_export($this->getContext(), true) : null;
    }

    /**
     * @return string
     */
    public function forTemplate(): string
    {
        return $this->encode();
    }

    /**
     * @return array|string
     */
    public function getResolverFunc()
    {
        return $this->resolverFunc;
    }

    /**
     * @param array|string $resolverFunc
     * @return EncodedResolver
     */
    public function setResolverFunc($resolverFunc)
    {
        $this->resolverFunc = $resolverFunc;
        return $this;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array $context
     * @return EncodedResolver
     */
    public function setContext(array $context): EncodedResolver
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param string $key
     * @param $val
     * @return EncodedResolver
     * @throws SchemaBuilderException
     */
    public function addContext(string $key, $val): EncodedResolver
    {
        Schema::invariant(
            is_scalar($val) || is_array($val),
            'Resolver context must be a scalar value or an array on %s',
            $this->getCallable()
        );

        $this->context[$key] = $val;

        return $this;
    }

}
