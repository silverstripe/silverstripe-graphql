<?php
namespace SilverStripe\GraphQL;

use GraphQL\Type\Definition\Type;

class FieldDefinition
{
    /**
     * Self documenting description for this field
     *
     * @var string
     */
    protected $description;

    /**
     * The GraphQL type (or string the type can be resolved from) of this field
     *
     * @var Type|string
     */
    protected $type;

    /**
     * A callable that can be used to resolve the value of this field. Takes the following parameters:
     *
     *  - $object mixed - The object to resolve the field from - eg. the DataObject
     *  - $args array -
     *  - $context array - Context for this resolve - includes details like `currentUser` (logged in `Member`)
     *  - $info GraphQL\Type\Definition\ResolveInfo - Additional info to be used for resolving this
     *
     * @var callable
     */
    protected $resolver;

    /**
     * @param string $description
     * @param Type|string $type
     * @param callable $resolver
     */
    public function __construct($description, $type, callable $resolver)
    {
        $this->description = $description;
        $this->type = $type;
        $this->resolver = $resolver;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return Type|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return callable
     */
    public function getResolver()
    {
        return $this->resolver;
    }
}
