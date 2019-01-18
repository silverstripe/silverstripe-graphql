<?php

namespace SilverStripe\GraphQL\GraphQLPHP;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeEncoderRegistryInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryEncoderInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface;
use SilverStripe\GraphQL\Schema\Components\AbstractType;

class SchemaEncoder implements TypeRegistryEncoderInterface
{
    const CLASS_NAME_PREFIX = 'TypeRegistry';

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var AbstractType[]
     */
    protected $types = [];

    /**
     * @var TypeEncoderRegistryInterface
     */
    protected $encoderRegistry;

    /**
     * SchemaEncoder constructor.
     * @param string $identifier
     * @param TypeEncoderRegistryInterface $encoderRegistry
     */
    public function __construct($identifier, TypeEncoderRegistryInterface $encoderRegistry)
    {
        $this->identifier = $identifier;
        $this->encoderRegistry = $encoderRegistry;
    }

    /**
     * @param AbstractType $type
     * @return $this
     */
    public function addType(AbstractType $type)
    {
        $this->types[$type->getName()] = $type;

        return $this;
    }

    /**
     * @param AbstractType[] $types
     * @return $this
     */
    public function addTypes($types)
    {
        foreach ($types as $type) {
            $this->addType($type);
        }

        return $this;
    }

    /**
     * @param AbstractType $type
     * @return $this
     */
    public function removeType(AbstractType $type)
    {
        unset($this->types[$type->getName()]);

        return $this;
    }

    /**
     * @return string
     * @throws Error
     */
    public function encode()
    {
        $factory = new BuilderFactory();
        $use = $factory->use(Type::class);
        $class = $factory
            ->class($this->getRegistryClassName())
            ->implement(TypeRegistryInterface::class)
            ->makeFinal()
            ->addStmt($factory->property('types')->makePrivate()->setDefault([]))
            ->addStmt(
                $factory->method('hasType')
                    ->makePublic()
                    ->addParam($factory->param('name'))
                    ->setDocComment('/**
                     * @param string $name
                     * @return bool
                     */')
                    ->addStmt(
                        new Return_(
                            new FuncCall(
                                new Name('method_exists'),
                                [
                                    new Variable('this'),
                                    new Variable('name')
                                ]
                            )
                        )
                    )
            )
            ->addStmt(
                $factory->method('getType')
                    ->makePublic()
                    ->addParam($factory->param('name'))
                    ->setDocComment('/**
                     * @param string $name
                     * @return Type|null
                     */')
                    ->addStmt(
                        new If_(
                            new BooleanNot(
                                new Isset_([
                                    new ArrayDimFetch(
                                        $factory->propertyFetch($factory->var('this'), 'types'),
                                        $factory->var('name')
                                    )
                                ])
                            ),
                            [
                                'stmts' => [
                                    new Expression(
                                        new Assign(
                                            new ArrayDimFetch(
                                                $factory->propertyFetch($factory->var('this'), 'types'),
                                                $factory->var('name')
                                            ),
                                            new MethodCall(
                                                $factory->var('this'),
                                                $factory->var('name')
                                            )
                                        )
                                    )
                                ]
                            ]
                        )
                    )
                    ->addStmt(
                        new Return_(
                            new ArrayDimFetch(
                                $factory->propertyFetch($factory->var('this'), 'types'),
                                $factory->var('name')
                            )
                        )
                    )
            );
        foreach ($this->generateTypeFunctions() as $name => $expr) {
            $class->addStmt(
                $factory->method($name)
                    ->makePrivate()
                    ->addStmt(new Return_($expr))
            );
        }
        $stmts = [$use->getNode(), $class->getNode()];
        $prettyPrinter = new Standard();

        $code = $prettyPrinter->prettyPrintFile($stmts);
        echo $code;
        file_put_contents($this->getCacheFile(), $code);
    }

    /**
     * @return bool
     */
    public function isEncoded()
    {
        return file_exists($this->getCacheFile());
    }

    /**
     * @return TypeRegistryInterface
     */
    public function getRegistry()
    {
        $cacheFile = $this->getCacheFile();
        $className = $this->getRegistryClassName();

        include $cacheFile;

        return new $className();
    }

    /**
     * @return string
     */
    public function getCacheFile()
    {
        return TEMP_PATH . DIRECTORY_SEPARATOR . ".cache.schema.{$this->identifier}";
    }

    /**
     * @return string
     */
    protected function getRegistryClassName()
    {
        return self::CLASS_NAME_PREFIX . '_' . sha1($this->identifier);
    }

    /**
     * @return \Generator
     */
    protected function generateTypeFunctions()
    {
        foreach ($this->types as $type) {
            $generator = $this->encoderRegistry->getEncoderForType($type);
            $expr = $generator->getExpression($type);
            $name = $type->getName();

            yield $name => $expr;
        }
    }
}
