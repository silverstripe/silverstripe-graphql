<?php


namespace SilverStripe\GraphQL\Schema;


use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\Token;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

class FieldAbstraction extends ViewableData implements ConfigurationApplier
{
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
     * FieldAbstraction constructor.
     * @param string|array $name
     * @param $config
     * @throws SchemaBuilderException
     */
    public function __construct(string $name, $config)
    {
        parent::__construct();
        $this->parseName($name);
        if (is_string($config)) {
            try {
                $ref = TypeReference::create($config);
                $ast = $ref->toAST();
                $this->encodedType = EncodedType::create($ast);
            } catch (SyntaxError $e) {
                throw new SchemaBuilderException(sprintf(
                    'The type for field "%s" is invalid: "%s"',
                    $this->name,
                    $config
                ));
            }
        }
        if (is_array($config)) {
            $this->applyConfig($config);
        }
    }

    public function applyConfig(array $config)
    {

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

}
