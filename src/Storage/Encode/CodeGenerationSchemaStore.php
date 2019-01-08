<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\SchemaConfig;
use SilverStripe\GraphQL\Storage\SchemaStorageInterface;
use Exception;

class CodeGenerationSchemaStore implements SchemaStorageInterface
{
    /**
     * @var TypeRegistryEncoderInterface
     */
    protected $encoder;

    /**
     * CodeGenerationSchemaStore constructor.
     * @param TypeRegistryEncoderInterface $encoder
     */
    public function __construct(TypeRegistryEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param SchemaConfig $schemaConfig
     * @param array $types
     * @return $this
     * @throws Exception
     */
    public function persist(SchemaConfig $schemaConfig, array $types)
    {
        $this->encoder->addTypes($types);
        $this->encoder->addType($schemaConfig->getQuery());
        $this->encoder->addType($schemaConfig->getMutation());
        $this->encoder->encode();

        return $this;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->encoder->isEncoded();
    }

    /**
     * @param SchemaConfig $schemaConfig
     * @return $this
     */
    public function loadIntoConfig(SchemaConfig $schemaConfig)
    {
        $registry = $this->encoder->getRegistry();
        $schemaConfig->setTypeLoader(function ($type) use ($registry) {
            return $registry->getType($type);
        });
        $schemaConfig->setQuery($registry->getType('Query'));
        $schemaConfig->setMutation($registry->getType('Mutation'));

        return $this;
    }

    /**
     * @return TypeRegistryEncoderInterface
     */
    public function getEncoder()
    {
        return $this->encoder;
    }
}