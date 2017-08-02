<?php

namespace SilverStripe\GraphQL\Scaffolding\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Scaffolding\Util\StringTypeParser;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Interfaces\TypeParserInterface;
use SilverStripe\GraphQL\Scaffolding\Util\ObjectTypeParser;
use SilverStripe\GraphQL\Manager;
use Exception;

class TypeCreatorExtension extends DataExtension
{
    protected $typeParser;

    public function setTypeParser(TypeParserInterface $typeParser)
    {
        $this->typeParser = $typeParser;
    }

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
                ObjectTypeParser::class
            ));
        }

        return Injector::inst()->createWithArgs(
            TypeParserInterface::class.'.string',
            [$type]
        );
    }

    public function getGraphQLType(Manager $manager = null)
    {
        if (!$this->isInternalGraphQLType()) {
            return $manager->getType($this->typeParser->getArgTypeName());
        }
        return $this->createTypeParser()->getType();
    }

    public function isInternalGraphQLType()
    {
        $type = $this->createTypeParser()->getType()->name;

        return is_scalar($type) && StringTypeParser::isInternalType($type);
    }

    protected function configType()
    {
        return Config::inst()->get(get_class($this->owner), 'graphql_type');
    }
}