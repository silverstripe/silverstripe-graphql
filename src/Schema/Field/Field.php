<?php

namespace SilverStripe\GraphQL\Schema\Field;


use GraphQL\Language\Token;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Plugin\PluginConsumer;
use SilverStripe\GraphQL\Schema\Registry\ResolverRegistry;
use SilverStripe\GraphQL\Schema\Resolver\EncodedResolver;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\EncodedType;
use SilverStripe\GraphQL\Schema\Type\TypeReference;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

class Field extends ViewableData implements ConfigurationApplier, SchemaValidator
{
    use PluginConsumer;

    const DEFAULT_TYPE = 'String';

    /**
     * @var ResolverRegistry
     */
    private $resolverRegistry;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Argument[]
     */
    private $args = [];

    /**
     * @var string|EncodedType
     */
    private $type;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var ResolverReference|null
     */
    private $resolver;

    /**
     * @var ResolverReference|null
     */
    private $defaultResolver;

    /**
     * @var array
     */
    private $resolverContext = [];

    /**
     * @var EncodedResolver[]
     */
    private $resolverMiddlewares = [];

    /**
     * Field constructor.
     * @param string|array $name
     * @param array|string $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, $config)
    {
        parent::__construct();
        $this->setResolverRegistry(Injector::inst()->get(ResolverRegistry::class));
        list ($name, $args) = static::parseName($name);
        $this->setName($name);

        Schema::invariant(
            is_string($config) || is_array($config),
            'Config for field %s must be a string or array. Got %s',
            $name,
            Field::class,
            gettype($config)
        );
        $appliedConfig = is_string($config) ? ['type' => $config] : $config;
        if ($args) {
            $configArgs = $config['args'] ?? [];
            $appliedConfig['args'] = array_merge($configArgs, $args);
        }
        $this->applyConfig($appliedConfig);
    }

    /**
     * @param string $def
     * @throws SchemaBuilderException
     * @return array
     */
    public static function parseName(string $def): array
    {
        $name = null;
        $args = null;
        if (stristr($def, Token::PAREN_L) !== false) {
            list ($name, $args) = explode(Token::PAREN_L, $def);
        } else {
            $name = $def;
        }
        Schema::assertValidName($name);

        if (!$args) {
            return [$name, []];
        }

        preg_match('/^(.*?)\)$/', $args, $matches);

        Schema::invariant(
            $matches,
            'Could not parse args on "%s"',
            $def
        );
        $argList = [];
        $argDefs = explode(',', $matches[1]);
        foreach ($argDefs as $argDef) {
            Schema::invariant(
                stristr($argDef, Token::COLON) !== false,
                'Invalid arg: %s',
                $argDef
            );
            list ($argName, $argType) = explode(':', $argDef);
            $argList[trim($argName)] = trim($argType);
        }
        return [$name, $argList];
    }

    /**
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        Schema::assertValidConfig($config, [
            'type',
            'args',
            'description',
            'resolver',
            'resolverContext',
            'defaultResolver',
            'plugins',
        ]);

        $type = $config['type'] ?? null;
        if ($type) {
            $this->setType($type);
        }

        if (isset($config['description'])) {
            $this->setDescription($config['description']);
        }
        if (isset($config['resolver'])) {
            $this->setResolver($config['resolver']);
        }
        if (isset($config['defaultResolver'])) {
            $this->setDefaultResolver($config['defaultResolver']);
        }
        if (isset($config['resolverContext'])) {
            $this->setResolverContext($config['resolverContext']);
        }

        $plugins = $config['plugins'] ?? [];
        $this->setPlugins($plugins);
        $args = $config['args'] ?? [];
        $this->setArgs($args);
    }

    /**
     * @param string $argName
     * @param null $config
     * @param callable|null $callback
     * @return Field
     */
    public function addArg(string $argName, $config, ?callable $callback = null): self
    {
        $argObj = $config instanceof Argument ? $config : Argument::create($argName, $config);
        $this->args[$argObj->getName()] = $argObj;
        if ($callback) {
            call_user_func_array($callback, [$argObj]);
        }
        return $this;
    }

    /**
     * @param array $args
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setArgs(array $args): self
    {
        Schema::assertValidConfig($args);
        foreach ($args as $argName => $config) {
            if ($config === false) {
                continue;
            }
            $this->addArg($argName, $config);
        }

        return $this;
    }

    /**
     * @param Field $field
     * @return Field
     */
    public function mergeWith(Field $field): self
    {
        foreach ($field->getArgs() as $arg) {
            $this->args[$arg->getName()] = $arg;
        }
        $this->mergePlugins($field->getPlugins());

        return $this;
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return $this->getEncodedType()->isList();
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->getEncodedType()->isRequired();
    }

    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void
    {
        Schema::invariant(
            $this->type,
            'Field %s has no type defined',
            $this->getName()
        );
    }

    /**
     * @param $type
     * @return Field
     * @throws SchemaBuilderException
     */
    public function setType($type): self
    {
        Schema::invariant(
            is_string($type) || $type instanceof EncodedType,
            '%s::%s must be a string or an instance of %s',
            __CLASS__,
            __FUNCTION__,
            EncodedType::class
        );

        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Field
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Argument[]
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return ArrayList
     */
    public function getArgList(): ArrayList
    {
        return ArrayList::create(array_values($this->args));
    }

    /**
     * @return EncodedType
     * @throws SchemaBuilderException
     */
    public function getEncodedType(): EncodedType
    {
        Schema::invariant(
            $this->type,
            'Field %s has no type defined.',
            $this->getName()
        );
        return $this->type instanceof EncodedType ? $this->type : $this->toEncodedType($this->type);
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     */
    public function getNamedType(): string
    {
        return $this->getEncodedType()->getTypeName()[0];
    }

    /**
     * @param string|null $typeName
     * @return EncodedResolver
     */
    public function getEncodedResolver(?string $typeName = null): EncodedResolver
    {
        if ($this->getResolver()) {
            $encodedResolver = EncodedResolver::create($this->getResolver(), $this->getResolverContext());
        } else {
            $resolver = $this->getResolverRegistry()->findResolver(
                $typeName,
                $this->name,
                $this->getDefaultResolver()
            );
            $encodedResolver = EncodedResolver::create($resolver, $this->getResolverContext());
        }

        foreach ($this->resolverMiddlewares as $middlewareRef) {
            $encodedResolver->addMiddleware($middlewareRef);
        }
        return $encodedResolver;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return Field
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return ResolverReference|null
     */
    public function getResolver(): ?ResolverReference
    {
        return $this->resolver;
    }

    /**
     * @param array|string|ResolverReference|null $resolver
     * @return Field
     */
    public function setResolver($resolver): self
    {
        if ($resolver) {
            $this->resolver = $resolver instanceof ResolverReference
                ? $resolver
                : ResolverReference::create($resolver);
        } else {
            $this->resolver = null;
        }

        return $this;
    }

    /**
     * @return ResolverReference|null
     */
    public function getDefaultResolver(): ?ResolverReference
    {
        return $this->defaultResolver;
    }

    /**
     * @param array|string|ResolverReference|null $defaultResolver
     * @return Field
     */
    public function setDefaultResolver($defaultResolver): self
    {
        if ($defaultResolver) {
            $this->defaultResolver = $defaultResolver instanceof ResolverReference
                ? $defaultResolver
                : ResolverReference::create($defaultResolver);
        } else {
            $this->defaultResolver = null;
        }

        return $this;
    }

    /**
     * @return ResolverRegistry
     */
    public function getResolverRegistry(): ResolverRegistry
    {
        return $this->resolverRegistry;
    }

    /**
     * @param ResolverRegistry $resolverRegistry
     * @return $this
     */
    public function setResolverRegistry(ResolverRegistry $resolverRegistry): self
    {
        $this->resolverRegistry = $resolverRegistry;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getResolverContext(): ?array
    {
        return $this->resolverContext;
    }

    /**
     * @param array|null $resolverContext
     * @return Field
     */
    public function setResolverContext(?array $resolverContext): self
    {
        $this->resolverContext = $resolverContext;
        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @return Field
     */
    public function addResolverContext(string $key, $value): self
    {
        $this->resolverContext[$key] = $value;

        return $this;
    }


    /**
     * @param array|string|ResolverReference|null $middleware
     * @param array|null $context
     * @return Field
     */
    public function addResolverMiddleware($middleware, ?array $context = null): self
    {
        if ($middleware) {
            $ref = $middleware instanceof ResolverReference
                ? $middleware
                : ResolverReference::create($middleware);
            $this->resolverMiddlewares[] = EncodedResolver::create($ref, $context);
        }

        return $this;
    }

    /**
     * @param string $type
     * @return EncodedType
     */
    private function toEncodedType(string $type): EncodedType
    {
        $ref = TypeReference::create($type);
        $ast = $ref->toAST();

        return EncodedType::create($ast);
    }

}
