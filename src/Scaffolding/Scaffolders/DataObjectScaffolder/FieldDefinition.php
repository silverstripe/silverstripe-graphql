<?php
namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;

/**
 * Defines a field that will be scaffolded using the DataObjectScaffolder. Create a
 */
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
     * This can be:
     *  - A GraphQL type object
     *  - A string that can be used to retrieve the type from the GraphQL manager ($manager->getGraphQLType())
     *  - An object that has the `TypeCreatorExtension` extension
     *
     * @var Type|TypeCreatorExtension|string
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
     * @param string $name
     * @param string $description
     * @param Type|TypeCreatorExtension|string $type
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
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Type|TypeCreatorExtension|string
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
