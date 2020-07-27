<?php


namespace SilverStripe\GraphQL\Schema\Resolver;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\Encoder;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

class EncodedResolver extends ViewableData implements Encoder
{
    /**
     * @var ResolverReference
     */
    private $resolverRef;

    /**
     * @var array
     */
    private $context = [];

    /**
     * @var EncodedResolver[]
     */
    private $middleware = [];

    /**
     * EncodedResolver constructor.
     * @param ResolverReference $resolver
     * @param array $context
     */
    public function __construct(ResolverReference $resolver, array $context = [])
    {
        parent::__construct();
        $this->resolverRef = $resolver;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function encode(): string
    {
        return $this->renderWith(__NAMESPACE__ . '\\Resolver');
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        $callable = sprintf(
            "['%s', '%s']",
            $this->resolverRef->getClass(),
            $this->resolverRef->getMethod()
        );
        if (empty($this->getContext())) {
            return $callable;
        }

        return sprintf(
            'call_user_func_array(%s, [%s])',
            $callable,
            $this->getContextArgs()
        );
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
    public function setContext(array $context): self
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
    public function addContext(string $key, $val): self
    {
        Schema::invariant(
            is_scalar($val) || is_array($val),
            'Resolver context must be a scalar value or an array on %s',
            $this->resolverRef->toString()
        );

        $this->context[$key] = $val;

        return $this;
    }

    /**
     * @param EncodedResolver $ref
     * @return EncodedResolver
     */
    public function addMiddleware(EncodedResolver $ref): self
    {
        $this->middleware[] = $ref;

        return $this;
    }

    /**
     * @return ArrayList
     */
    public function getResolverMiddlewares(): ArrayList
    {
        return ArrayList::create($this->middleware);
    }
}
