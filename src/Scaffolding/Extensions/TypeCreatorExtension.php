<?php

namespace SilverStripe\GraphQL\Scaffolding\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Scaffolding\Util\StringTypeParser;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Interfaces\TypeParserInterface;
use SilverStripe\GraphQL\Scaffolding\Util\ArrayTypeParser;
use SilverStripe\GraphQL\Manager;
use Exception;

/**
 * Adds functionality to associate an object with a given GraphQL type, either
 * internal (e.g. String) or complex (e.g. object)
 */
class TypeCreatorExtension extends DataExtension
{
    /**
     * @var TypeParserInterface
     */
    protected $typeParser;

    /**
     * @param TypeParserInterface $typeParser
     */
    public function setTypeParser(TypeParserInterface $typeParser)
    {
        $this->typeParser = $typeParser;
    }

    /**
     * Creates the type parser, using the `graphql_type` value if a string
     * or the assigned typeParser service if a complex type
     *
     * @return TypeParserInterface
     * @throws Exception
     */
    public function createTypeParser()
    {
        if ($this->typeParser) {
            return $this->typeParser;
        }

        $type = $this->configType();

        if (is_array($type)) {
            throw new Exception(sprintf(
                '%s.graphql_type is an array. To define object types, use Injector to inject
                a suitable parser (e.g. %s) on the $typeParser property',
                get_class($this->owner),
                ArrayTypeParser::class
            ));
        }

        return Injector::inst()->createWithArgs(
            TypeParserInterface::class . '.string',
            [$type]
        );
    }

    /**
     * Creates the type using appropriate parser
     *
     * @param Manager|null $manager
     * @return \GraphQL\Type\Definition\Type
     */
    public function getGraphQLType(Manager $manager = null)
    {
        if (!$this->isInternalGraphQLType()) {
            return $manager->getType($this->typeParser->getName());
        }
        return $this->createTypeParser()->getType();
    }

    /**
     * Returns true if this class uses an internal GraphQL type, e.g. String
     *
     * @return bool
     */
    public function isInternalGraphQLType()
    {
        $type = $this->createTypeParser()->getType()->name;

        return is_scalar($type) && StringTypeParser::isInternalType($type);
    }

    /**
     * Gets the graphql type from config
     *
     * @return string
     */
    protected function configType()
    {
        return Config::inst()->get(get_class($this->owner), 'graphql_type');
    }
}
