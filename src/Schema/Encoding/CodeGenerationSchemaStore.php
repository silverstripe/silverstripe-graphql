<?php

namespace SilverStripe\GraphQL\Schema\Encoding;

use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryEncoderInterface;
use SilverStripe\GraphQL\Schema\SchemaStorageInterface;
use Exception;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\FieldCollection;
use SilverStripe\GraphQL\Schema\Components\Schema;

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
     * @return Schema
     */
    public function load()
    {
        return new Schema(
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
     * @param Field[] $queries
     * @return FieldCollection
     */
    protected function createQuery(array $queries)
    {
        $type = new FieldCollection('Query');
        $type->setFields($queries);

        return $type;
    }

    /**
     * @param Field[] $mutations
     * @return FieldCollection
     */
    protected function createMutation(array $mutations)
    {
        $type = new FieldCollection('Mutation');
        $type->setFields($mutations);

        return $type;
    }
}
