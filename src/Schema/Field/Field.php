<?php

namespace SilverStripe\GraphQL\Schema\Field;

use GraphQL\Language\Token;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\PluginValidator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaComponent;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Interfaces\SignatureProvider;
use SilverStripe\GraphQL\Schema\Plugin\PluginConsumer;
use SilverStripe\GraphQL\Schema\Resolver\EncodedResolver;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\EncodedType;
use SilverStripe\GraphQL\Schema\Type\TypeReference;
use Exception;

/**
 * An abstraction of a field that appears on a Type abstraction
 */
class Field implements
    ConfigurationApplier,
    SchemaValidator,
    SignatureProvider,
    SchemaComponent,
    PluginValidator
{
    use Injectable;
    use Configurable;
    use PluginConsumer;

    const DEFAULT_TYPE = 'String';

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
     * @var string
     */
    private $typeAsModel;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var ResolverReference|null
     */
    private $resolver;

    /**
     * Key/value pairs to pass to the resolver. This is useful for creating a dynamic resolver
     * from a static callable.
     *
     * @var array
     */
    private $resolverContext = [];

    /**
     * Functions to invoke before the resolver that mutate the object
     * @var EncodedResolver[]
     */
    private $resolverMiddlewares = [];

    /**
     * Functions to invoke after the resolver that mutate the result
     * @var EncodedResolver[]
     */
    private $resolverAfterwares = [];

    /**
     * Field constructor.
     * @param string $name
     * @param array|string $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, $config = [])
    {
        list ($name, $args) = static::parseName($name);
        $this->setName($name);

        Schema::invariant(
            is_string($config) || is_array($config),
            'Config for field %s must be a string or array. Got %s',
            $name,
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
     * Negotiates a variety of syntax that can appear in a field name definition.
     *
     * fieldName
     * fieldName(arg1: String!, arg2: Int)
     * fieldName(arg1: String! = "foo")
     *
     * @param string $def
     * @throws SchemaBuilderException
     * @return array
     */
    public static function parseName(string $def): array
    {
        $name = null;
        $args = null;
        if (stristr($def ?? '', Token::PAREN_L ?? '') !== false) {
            list ($name, $args) = explode(Token::PAREN_L ?? '', $def ?? '');
        } else {
            $name = $def;
        }
        Schema::assertValidName($name);

        if (!$args) {
            return [$name, []];
        }

        preg_match('/^(.*?)\)$/', $args ?? '', $matches);

        Schema::invariant(
            $matches,
            'Could not parse args on "%s"',
            $def
        );
        $argList = [];
        $argDefs = explode(',', $matches[1] ?? '');
        foreach ($argDefs as $argDef) {
            Schema::invariant(
                stristr($argDef ?? '', Token::COLON ?? '') !== false,
                'Invalid arg: %s',
                $argDef
            );
            list ($argName, $argType) = explode(':', $argDef ?? '');
            $argList[trim($argName)] = trim($argType ?? '');
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
            'model',
            'args',
            'description',
            'resolver',
            'resolverContext',
            'resolvedModelClass',
            'plugins',
        ]);

        $type = $config['type'] ?? null;
        $modelTypeDef = $config['model'] ?? null;
        if ($modelTypeDef) {
            $this->setTypeAsModel($modelTypeDef);
        }
        if ($type) {
            $this->setType($type);
        }

        if (isset($config['description'])) {
            $this->setDescription($config['description']);
        }
        if (isset($config['resolver'])) {
            $this->setResolver($config['resolver']);
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
            $this->args[$arg->getName()] = clone $arg;
        }
        $this->mergePlugins($field->getPlugins());

        return $this;
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return $this->getTypeRef()->isList();
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->getTypeRef()->isRequired();
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
     * @param bool $required
     * @return Field
     * @throws SchemaBuilderException
     */
    public function setType($type, $required = false): self
    {
        Schema::invariant(
            !is_array($type),
            'Type on %s is an array. Did you forget to quote the [TypeName] syntax in your YAML?',
            $this->getName()
        );
        Schema::invariant(
            is_string($type) || $type instanceof EncodedType,
            '%s::%s must be a string or an instance of %s',
            __CLASS__,
            __FUNCTION__,
            EncodedType::class
        );

        $this->type = sprintf('%s%s', $type, $required ? '!' : '');

        return $this;
    }

    /**
     * @param string $modelTypeDef
     * @return $this
     */
    public function setTypeAsModel(string $modelTypeDef): self
    {
        $this->typeAsModel = $modelTypeDef;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTypeAsModel(): ?string
    {
        return $this->typeAsModel;
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
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return Argument[]
     */
    public function getArgs(): array
    {
        return $this->args;
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
        return $this->type instanceof EncodedType
            ? $this->type
            : EncodedType::create($this->getTypeRef());
    }

    /**
     * Gets the name of the type, ignoring any nonNull/listOf wrappers
     *
     * @return string
     */
    public function getNamedType(): string
    {
        return $this->getTypeRef()->getNamedType();
    }

    /**
     * [MyType!]! becomes [MyNewType!]!
     * @param string $name
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setNamedType(string $name): self
    {
        $currentType = $this->getType();
        $newType = preg_replace('/[A-Za-z_0-9]+/', $name ?? '', $currentType ?? '');
        return $this->setType($newType);
    }

    /**
     * @param string|null $typeName
     * @return EncodedResolver
     * @throws SchemaBuilderException
     */
    public function getEncodedResolver(?string $typeName = null): EncodedResolver
    {
        $resolver = $this->getResolver();
        Schema::invariant(
            $resolver,
            'Cannot get encoded resolver before one has been assigned on type %s, field %s',
            $typeName,
            $this->getName()
        );
        $encodedResolver = EncodedResolver::create($resolver, $this->getResolverContext());

        foreach ($this->resolverMiddlewares as $middlewareRef) {
            $encodedResolver->addMiddleware($middlewareRef);
        }
        foreach ($this->resolverAfterwares as $afterwareRef) {
            $encodedResolver->addAfterware($afterwareRef);
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
     * @param array|string|ResolverReference|null $resolver
     * @param array|null $context
     * @return $this
     */
    public function addResolverMiddleware($resolver, ?array $context = null): self
    {
        return $this->decorateResolver(EncodedResolver::MIDDLEWARE, $resolver, $context);
    }

    /**
     * @param array|string|ResolverReference|null $resolver
     * @param array|null $context
     * @return $this
     */
    public function addResolverAfterware($resolver, ?array $context = null): self
    {
        return $this->decorateResolver(EncodedResolver::AFTERWARE, $resolver, $context);
    }

    /**
     * @return EncodedResolver[]
     */
    public function getResolverMiddlewares(): array
    {
        return $this->resolverMiddlewares;
    }

    /**
     * @return EncodedResolver[]
     */
    public function getResolverAfterwares(): array
    {
        return $this->resolverAfterwares;
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     * @throws Exception
     */
    public function getSignature(): string
    {
        $args = $this->getArgs();
        usort($args, function (Argument $a, Argument $z) {
            return $a->getName() <=> $z->getName();
        });

        $components = [
            $this->getName(),
            $this->getEncodedType()->encode(),
            $this->getEncodedResolver()->getExpression(),
            $this->getDescription(),
            $this->getSortedPlugins(),
            array_map(function (Argument $arg) {
                return $arg->getSignature();
            }, $args ?? []),
        ];

        return md5(json_encode($components) ?? '');
    }

    /**
     * @param string $pluginName
     * @param $plugin
     * @throws SchemaBuilderException
     */
    public function validatePlugin(string $pluginName, $plugin): void
    {
        Schema::invariant(
            $plugin instanceof FieldPlugin,
            'Plugin %s does not apply to field "%s"',
            $pluginName,
            $this->getName()
        );
    }

    /**
     * @param string $position
     * @param array|string|ResolverReference|null $resolver
     * @param array|null $context
     * @return Field
     */
    private function decorateResolver(string $position, $resolver, ?array $context = null): self
    {
        if ($resolver) {
            $ref = $resolver instanceof ResolverReference
                ? $resolver
                : ResolverReference::create($resolver);
            if ($position === EncodedResolver::MIDDLEWARE) {
                $this->resolverMiddlewares[] = EncodedResolver::create($ref, $context);
            } elseif ($position === EncodedResolver::AFTERWARE) {
                $this->resolverAfterwares[] = EncodedResolver::create($ref, $context);
            }
        }

        return $this;
    }

    /**
     * @return TypeReference
     */
    private function getTypeRef(): TypeReference
    {
        return TypeReference::create($this->type);
    }
}
