<?php

namespace SilverStripe\GraphQL\Schema;


use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\Parser;
use GraphQL\Language\Token;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;
use ReflectionException;

class FieldAbstraction extends ViewableData implements ConfigurationApplier
{
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
     * @var ArgumentAbstraction[]
     */
    private $args = [];

    /**
     * @var EncodedType
     */
    private $encodedType;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var array|null
     */
    private $resolver;

    /**
     * @var array|null
     */
    private $defaultResolver;

    /**
     * @var array|null
     */
    private $resolverContext;

    /**
     * FieldAbstraction constructor.
     * @param string|array $name
     * @param array|string $config
     * @throws SchemaBuilderException
     * @throws ReflectionException
     */
    public function __construct(string $name, $config)
    {
        parent::__construct();
        $this->setResolverRegistry(Injector::inst()->get(ResolverRegistry::class));
        list ($name, $args) = static::parseName($name);
        $this->setName($name);
        $this->applyArgs($args);

        SchemaBuilder::invariant(
            is_string($config) || is_array($config),
            'Config for field %s must be a string or array',
            $name
        );
        if (is_string($config)) {
            $this->applyType($config);
        } else {
            $this->applyConfig($config);
        }
    }

    /**
     * @param array $config
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
    {
        SchemaBuilder::assertValidConfig($config, [
            'type',
            'args',
            'description',
            'resolver',
            'resolverContext',
            'defaultResolver',
        ]);

        $type = $config['type'] ?? null;
        SchemaBuilder::invariant($type, 'Field %s has no type defined', $this->name);
        $this->applyType($type);

        $description = $config['description'] ?? null;
        $args = $config['args'] ?? [];
        $resolver = $config['resolver'] ?? null;
        $defaultResolver = $config['defaultResolver'] ?? null;
        $resolverCreator = $config['resolverContext'] ?? null;

        foreach ([$resolver, $defaultResolver] as $callable) {
            SchemaBuilder::invariant(
                $callable === null || (is_array($callable) && count($callable) === 2),
                'Resolvers must be an array tuple of class name, method name'
            );
        }
        $this->setDescription($description);
        $this->applyArgs($args);
        $this->setResolver($resolver);
        $this->setDefaultResolver($defaultResolver);
        $this->setResolverContext($resolverCreator);
    }

    /**
     * @param string|EncodedType $type
     * @return $this
     * @throws SchemaBuilderException
     */
    public function applyType($type): self
    {
        $encodedType = $type instanceof EncodedType ? $type : $this->toEncodedType($type);
        return $this->setEncodedType($encodedType);
    }

    /**
     * @param array $args
     * @throws SchemaBuilderException
     * @return $this
     */
    public function applyArgs(array $args): self
    {
        SchemaBuilder::assertValidConfig($args);
        foreach ($args as $argName => $config) {
            if ($config === false) {
                continue;
            }
            SchemaBuilder::assertValidName($argName);
            if (is_string($config)) {
                $this->args[$argName] = ArgumentAbstraction::create(
                    $argName,
                    $config
                );
            } else {
                SchemaBuilder::assertValidConfig($config);
                $type = $config['type'] ?? null;
                SchemaBuilder::invariant(
                    $type,
                    'Argument %s on %s has no type defined',
                    $argName,
                    $this->name
                );
                $argAbstract = ArgumentAbstraction::create($argName, $type);
                $argAbstract->applyConfig($config);
                $this->args[$argName] = $argAbstract;
            }
        }

        return $this;
    }

    /**
     * @param string $type
     * @return EncodedType
     * @throws SchemaBuilderException
     */
    private function toEncodedType(string $type): EncodedType
    {
        try {
            $ref = TypeReference::create($type);
            $ast = $ref->toAST();
            return EncodedType::create($ast);
        } catch (SyntaxError $e) {
            throw new SchemaBuilderException(sprintf(
                'The type for field "%s" is invalid: "%s"',
                $this->name,
                $type
            ));
        }
    }


    /**
     * @param string $def
     * @throws SchemaBuilderException
     * @throws ReflectionException
     * @return array
     */
    public static function parseName(string $def): array
    {
        $name = null;
        $args = null;
        $pos = strpos($def, Token::PAREN_L);
        if ($pos === false) {
            $name = $def;
        } else {
            $name = substr($def, 0, $pos);
            $args = substr($def, $pos);
        }
        try {
            $nameNode = Parser::name($name);
            SchemaBuilder::invariant(
                $nameNode instanceof NameNode,
                'Could not parse field name "%s"',
                $name
            );
            SchemaBuilder::assertValidName($nameNode->value);
            $name = $nameNode->value;
        } catch (SyntaxError $e) {
            throw new SchemaBuilderException(sprintf(
                'The name "%s" is not formatted correctly',
                $name
            ));
        }

        if (!$args) {
            return [$name, []];
        }

        try {
            // Not the hack it appears to be!
            // This API is meant to be public, but there is a bug
            // related to strict typing https://github.com/webonyx/graphql-php/issues/698

            $parser = new Parser($args, ['noLocation' => true]);
            $reflect = new \ReflectionClass(Parser::class);
            $expect = $reflect->getMethod('expect');
            $expect->setAccessible(true);
            $argMethod = $reflect->getMethod('parseArgumentsDefinition');
            $argMethod->setAccessible(true);
            $expect->invoke($parser, Token::SOF);
            $argsNode = $argMethod->invoke($parser);
            $expect->invoke($parser, Token::EOF);
            SchemaBuilder::invariant(
                $argsNode instanceof NodeList,
                'Could not parse args on "%s"',
                $def
            );
            $argList = [];
            foreach ($argsNode as $arg) {
                $argName = $arg->name->value;
                $argList[$argName] = [
                    'type' => EncodedType::create($arg->type)
                ];
            }

            return [$name, $argList];
        } catch (SyntaxError $e) {
            throw new SchemaBuilderException(sprintf(
                'The arguments for %s are not formatted correctly',
                $name
            ));
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return FieldAbstraction
     */
    public function setName(string $name): FieldAbstraction
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        $this->args;
    }

    /**
     * @return ArrayList
     */
    public function getArgList(): ArrayList
    {
        return ArrayList::create(array_values($this->args));
    }

    /**
     * @param ArgumentAbstraction[] $args
     * @return FieldAbstraction
     */
    public function setArgs(array $args): FieldAbstraction
    {
        $this->args = $args;
        return $this;
    }

    /**
     * @return EncodedType
     */
    public function getEncodedType(): EncodedType
    {
        return $this->encodedType;
    }

    /**
     * @param EncodedType $encodedType
     * @return FieldAbstraction
     */
    public function setEncodedType(EncodedType $encodedType): FieldAbstraction
    {
        $this->encodedType = $encodedType;
        return $this;
    }

    /**
     * @param string|null $typeName
     * @return EncodedResolver
     */
    public function getEncodedResolver(?string $typeName = null): EncodedResolver
    {
        if ($this->getResolver()) {
            return EncodedResolver::create($this->getResolver())
                ->setContext($this->getResolverContext());
        }
        $resolver = $this->getResolverRegistry()->findResolver(
            $typeName,
            $this->name,
            $this->getDefaultResolver()
        );

        return EncodedResolver::create($resolver)
            ->setContext($this->getResolverContext());
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
     * @return FieldAbstraction
     */
    public function setDescription(?string $description): FieldAbstraction
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getResolver(): ?array
    {
        return $this->resolver;
    }

    /**
     * @param array|null $resolver
     * @return FieldAbstraction
     */
    public function setResolver(?array $resolver): FieldAbstraction
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getDefaultResolver(): ?array
    {
        return $this->defaultResolver;
    }

    /**
     * @param array|null $defaultResolver
     * @return FieldAbstraction
     */
    public function setDefaultResolver(?array $defaultResolver): FieldAbstraction
    {
        $this->defaultResolver = $defaultResolver;
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
    public function setResolverRegistry(ResolverRegistry $resolverRegistry): FieldAbstraction
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
     * @return FieldAbstraction
     */
    public function setResolverContext(?array $resolverContext): FieldAbstraction
    {
        $this->resolverContext = $resolverContext;
        return $this;
    }


}
