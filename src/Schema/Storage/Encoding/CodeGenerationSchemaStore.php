<?php

namespace SilverStripe\GraphQL\Schema\Storage\Encoding;

use GraphQL\Type\SchemaConfig;
use SilverStripe\GraphQL\Storage\Encode\TypeRegistryEncoderInterface;
use SilverStripe\GraphQL\Storage\SchemaStorageInterface;
use Exception;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\ObjectTypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\SchemaAbstraction;

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
     * @param array $types
     * @param array $queries
     * @param array $mutations
     * @return SchemaStorageInterface|void
     * @throws Exception
     */
    public function persist(array $types, $queries = [], $mutations = [])
    {
        $this->encoder->addTypes($types);
        if (!empty($queries)) {
            $this->encoder->addType($this->createQuery($queries));
        }

        if (!empty($mutations)) {
            $this->encoder->addType($this->createMutation($mutations));
        }

        $this->encoder->encode();
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->encoder->isEncoded();
    }

    /**
     * @return SchemaAbstraction
     */
    public function load()
    {
        return new SchemaAbstraction(
            $this->getEncoder()->getRegistry()
        );
        
    }

    /**
     * @return TypeRegistryEncoderInterface
     */
    public function getEncoder()
    {
        return $this->encoder;
    }

    /**
     * @param FieldAbstraction[] $queries
     * @return ObjectTypeAbstraction
     */
    protected function createQuery(array $queries)
    {
        $type = new ObjectTypeAbstraction('Query');
        $type->setFields($queries);

        return $type;
    }

    /**
     * @param FieldAbstraction[] $mutations
     * @return ObjectTypeAbstraction
     */
    protected function createMutation(array $mutations)
    {
        $type = new ObjectTypeAbstraction('Mutation');
        $type->setFields($mutations);

        return $type;
    }

}