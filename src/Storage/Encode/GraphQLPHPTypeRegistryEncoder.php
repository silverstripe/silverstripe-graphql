<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;
use LogicException;

class GraphQLPHPTypeRegistryEncoder implements TypeRegistryEncoderInterface
{
    const CLASS_NAME_PREFIX = 'TypeRegistry';

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var array
     */
    protected $types = [];

    /**
     * @var TypeEncoderInterface[]
     */
    protected $typeEncoders = [];

    /**
     * GraphQLPHPTypeRegistryEncoder constructor.
     * @param string $identifier
     * @param TypeEncoderInterface[] $encoders
     */
    public function __construct($identifier, ...$encoders)
    {
        $this->identifier = $identifier;
        $this->typeEncoders = $encoders;
    }

    /**
     * @param Type $type
     * @return $this
     */
    public function addType(Type $type)
    {
        $this->types[(string) $type] = $type;

        return $this;
    }

    /**
     * @param Type[] $types
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
     * @param Type $type
     * @return $this
     */
    public function removeType(Type $type)
    {
        unset($this->types[(string) $type]);

        return $this;
    }

    /**
     * @return string|void
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
                $factory->method('has')
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
                $factory->method('get')
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

        return $prettyPrinter->prettyPrintFile($stmts);
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
    protected function getCacheFile()
    {
        return TEMP_PATH . DIRECTORY_SEPARATOR . ".cache.{$this->identifier}";
    }

    /**
     * @return string
     */
    protected function getRegistryClassName()
    {
        $types = implode('', array_keys($this->types));

        return self::CLASS_NAME_PREFIX . '_' . sha1($this->identifier . $types);
    }

    /**
     * @return \Generator
     * @throws Error
     */
    protected function generateTypeFunctions()
    {
        foreach ($this->types as $type) {
            $generator = $this->getGeneratorForType($type);
            if (!$generator) {
                throw new LogicException(sprintf(
                    'Could not find a generator for type %s',
                    get_class($type)
                ));
            }

            $expr = $generator->getExpression($type);
            $name = $generator->getName($type);

            yield $name => $expr;
        }

    }

    /**
     * @param Type $type
     * @return null|TypeEncoderInterface
     * @throws Error
     */
    protected function getGeneratorForType(Type $type)
    {
        foreach ($this->typeEncoders as $encoder) {
            if ($encoder->appliesTo($type)) {
                $encoder->assertValid($type);
                return $encoder;
            }
        }

        return null;
    }
}