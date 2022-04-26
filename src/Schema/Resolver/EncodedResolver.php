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

    private ResolverReference $resolverRef;

    private ?array $context = [];

    /**
     * @var EncodedResolver[]
     */
    private array $middleware = [];

    /**
     * @var EncodedResolver[]
     */
    private array $afterware = [];

    public function __construct(ResolverReference $resolver, ?array $context = [])
    {
        $this->resolverRef = $resolver;
        $this->context = $context;
    }

    public function encode(): string
    {
        return Encoder::create(__DIR__ . '/templates/resolver.inc.php', $this)
            ->encode();
    }

    public function getStack(): array
    {
        return array_merge(
            $this->getResolverMiddlewares(),
            [$this],
            $this->getResolverAfterwares()
        );
    }

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

    public function getInnerExpression(): string
    {
        return sprintf(
            "['%s', '%s']",
            $this->resolverRef->getClass(),
            $this->resolverRef->getMethod()
        );
    }

    public function getContextArgs(): ?string
    {
        return !empty($this->getContext()) ? var_export($this->getContext(), true) : null;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getRef(): ResolverReference
    {
        return $this->resolverRef;
    }

    /**
     * @param string $key
     * @param mixed $val
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

    public function addMiddleware(EncodedResolver $ref): self
    {
        $this->middleware[] = $ref;

        return $this;
    }

    public function getResolverMiddlewares(): array
    {
        return $this->middleware;
    }

    public function addAfterware(EncodedResolver $ref): self
    {
        $this->afterware[] = $ref;

        return $this;
    }

    public function getResolverAfterwares(): array
    {
        return $this->afterware;
    }
}
