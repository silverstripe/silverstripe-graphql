<?php


namespace SilverStripe\GraphQL\Schema;


use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;

class EncodedResolver extends ViewableData implements Encoder
{
    /**
     * @var array|string
     */
    private $resolverFunc;

    /**
     * @var array|null
     */
    private $context;

    /**
     * EncodedResolver constructor.
     * @param string|array $resolverFunc
     * @param array|null $context
     * @throws SchemaBuilderException
     */
    public function __construct($resolverFunc, ?array $context = null)
    {
        parent::__construct();
        SchemaBuilder::invariant(
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
     * @return ArrayList
     * @throws SchemaBuilderException
     */
    public function getContextArgs(): ArrayList
    {
        $list = ArrayList::create();
        foreach ($this->getContext() as $arg) {
            SchemaBuilder::invariant(
                is_scalar($arg),
                'Args for dynamic resolvers must be scalar. Provided %s to %s',
                gettype($arg),
                $this->getCallable()
            );

            $data = ArrayData::create([
                'EncodedArg' => is_string($arg) ? sprintf("'%s'", addslashes($arg)) : $arg,
            ]);
            $list->push($data);
        }

        return $list;
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
     * @return array|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array|null $context
     * @return EncodedResolver
     */
    public function setContext(?array $context): EncodedResolver
    {
        $this->context = $context;
        return $this;
    }



}
