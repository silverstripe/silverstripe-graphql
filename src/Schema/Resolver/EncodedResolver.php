<?php


namespace SilverStripe\GraphQL\Schema\Resolver;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\Encoder as EncoderInterface;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Storage\Encoder;

/**
 * A resolver function that can be expressed in generated PHP code
 */
class EncodedResolver implements EncoderInterface
{
    const AFTERWARE = 'afterware';
    const MIDDLEWARE = 'middleware';

    use Injectable;
    use Configurable;

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
     * @var EncodedResolver[]
     */
    private $afterware = [];

    /**
     * EncodedResolver constructor.
     * @param ResolverReference $resolver
     * @param array|null $context
     */
    public function __construct(ResolverReference $resolver, ?array $context = [])
    {
        $this->resolverRef = $resolver;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function encode(): string
    {
        return Encoder::create(__DIR__ . '/templates/resolver.inc.php', $this)
            ->encode();
    }

    /**
     * @return array
     */
    public function getStack(): array
    {
        return array_merge(
            $this->getResolverMiddlewares(),
            [$this],
            $this->getResolverAfterwares()
        );
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        $callable = $this->getInnerExpression();
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
     * @return string
     */
    public function getInnerExpression(): string
    {
        return sprintf(
            "['%s', '%s']",
            $this->resolverRef->getClass(),
            $this->resolverRef->getMethod()
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
     * @return array|null
     */
    public function getContext(): ?array
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
     * @return ResolverReference
     */
    public function getRef(): ResolverReference
    {
        return $this->resolverRef;
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
     * @return array
     */
    public function getResolverMiddlewares(): array
    {
        return $this->middleware;
    }

    /**
     * @param EncodedResolver $ref
     * @return EncodedResolver
     */
    public function addAfterware(EncodedResolver $ref): self
    {
        $this->afterware[] = $ref;

        return $this;
    }

    /**
     * @return array
     */
    public function getResolverAfterwares(): array
    {
        return $this->afterware;
    }
}
