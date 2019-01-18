<?php

namespace SilverStripe\GraphQL\Scaffolding\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Util\StringTypeParser;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Interfaces\TypeParserInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Manager;
use Exception;

/**
 * Adds functionality to associate an object with a given GraphQL type, either
 * internal (e.g. String) or complex (e.g. object)
 */
class TypeCreatorExtension extends DataExtension implements ManagerMutatorInterface
{

    /**
     * Creates the type parser, using the `graphql_type` value
     *
     * @return TypeParserInterface
     * @throws Exception
     */
    public function createTypeParser()
    {
        $type = $this->configType();
        if (is_array($type)) {
            return Injector::inst()->createWithArgs(
                TypeParserInterface::class . '.array',
                [
                    StaticSchema::inst()->typeName(get_class($this->owner)),
                    $type
                ]
            );
        }

        return Injector::inst()->createWithArgs(
            TypeParserInterface::class . '.string',
            [(string) $type]
        );
    }

    /**
     * Creates the type using appropriate parser
     *
     * @return string
     * @throws Exception
     */
    public function getGraphQLType()
    {
        $type = $this->createTypeParser()->getType();
        $name = $type->getName();

        return $name;
    }

    /**
     * Returns true if the type parser creates an internal type e.g. String
     *
     * @return bool
     * @throws Exception
     */
    public function isInternalGraphQLType()
    {
        $type = $this->createTypeParser()->getType();

        return $this->isInternal($type->getName());
    }

    /**
     * Adds this object's GraphQL type to the Manager
     *
     * @param Manager $manager
     * @throws Exception
     */
    public function addToManager(Manager $manager)
    {
        $parser = $this->createTypeParser();
        $type = $parser->getType();
        if ($this->isInternal($type->getName())) {
            return;
        }
        $manager->addType($type, $parser->getName());
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

    /**
     * Returns true if the named of the type is an internal one, e.g. "String"
     *
     * @param  string $typeName
     * @return bool
     */
    protected function isInternal($typeName)
    {
        return is_scalar($typeName) && StringTypeParser::isInternalType($typeName);
    }
}
