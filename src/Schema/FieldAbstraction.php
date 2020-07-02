<?php


namespace SilverStripe\GraphQL\Schema;


use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\Token;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

class FieldAbstraction extends ViewableData implements ConfigurationApplier
{
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
     * @var EncodedResolver
     */
    private $resolverOverride;

    /**
     * FieldAbstraction constructor.
     * @param string|array $name
     * @param $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, $config)
    {
        parent::__construct();
        $this->setResolverRegistry(Injector::inst()->get(ResolverRegistry::class));
        $this->parseName($name);
        if (is_string($config)) {
            $this->setEncodedType($this->toEncodedType($config));
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
        SchemaBuilder::assertValidConfig($config, ['type', 'args', 'description', 'resolver']);

        $type = $config['type'] ?? null;
        SchemaBuilder::invariant($type, 'Field %s has no type defined', $this->name);
        $this->setEncodedType($this->toEncodedType($type));

        $description = $config['description'] ?? null;
        $args = $config['args'] ?? [];
        $resolver = $config['resolver'] ?? null;

        $this->setDescription($description);
        $this->applyArgs($args);

        if ($resolver) {
            SchemaBuilder::invariant(
                is_array($resolver) && is_callable($resolver),
                'Resolver for %s must be a callable (tuple of classname, method name)',
                $this->name
            );
            $this->setResolverOverride(EncodedResolver::create($resolver));
        }
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
     * @param array $args
     * @throws SchemaBuilderException
     */
    private function applyArgs(array $args): void
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
                $this->args[$argName] = ArgumentAbstraction::create($argName, $type)
                    ->applyConfig($config);
            }
        }
    }

    /**
     * @param string $name
     * @throws SchemaBuilderException
     */
    private function parseName(string $name)
    {
        $parser = new Parser(new Source($name), ['noLocation' => true]);
        $parser->skip(Token::SOF);
        try {
            $nameNode = $parser->parseName();
            SchemaBuilder::invariant(
                $nameNode instanceof NameNode,
                'Could not parse field name "%s"',
                $name
            );
            SchemaBuilder::assertValidName($nameNode->value);
            $this->name = $nameNode->value;
        } catch (SyntaxError $e) {
            throw new SchemaBuilderException(sprintf(
                'The name "%s" is not formatted correctly',
                $name
            ));
        }
        try {
            $args = $parser->parseArgumentDefs();
            SchemaBuilder::invariant(
                $args instanceof NodeList,
                'Could not parse args on "%s"',
                $name
            );
            foreach ($args as $arg) {
                $name = $arg->name->value;
                $this->args[$name] = ArgumentAbstraction::create(
                    $name,
                    EncodedType::create($arg->type)
                );
            }
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
     * @return EncodedResolver
     */
    public function getResolverOverride(): EncodedResolver
    {
        return $this->resolverOverride;
    }

    /**
     * @param EncodedResolver $resolverOverride
     * @return FieldAbstraction
     */
    public function setResolverOverride(EncodedResolver $resolverOverride): FieldAbstraction
    {
        $this->resolverOverride = $resolverOverride;
        return $this;
    }


    public function getEncodedResolver(?string $typeName = null): EncodedResolver
    {
        if ($this->resolverOverride) {
            return $this->resolverOverride;
        }

        $resolver = $this->getResolverRegistry()->findResolver($typeName, $this->name);

        return EncodedResolver::create($resolver);
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

}
