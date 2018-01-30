<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\ORM\ArrayLib;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\GraphQL\Scaffolding\Interfaces\TypeParserInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * Parses a map of type, e.g. Int!(20) into an array defining the arg type
 */
class ArrayTypeParser implements TypeParserInterface
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $fields;

    /**
     * TypeParser constructor.
     *
     * @param string|array $rawArg
     */
    public function __construct($name, $fields)
    {
        if (!ArrayLib::is_associative($fields)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s::__construct() second parameter must be an associative array
                of field names to field types',
                    __CLASS__
                )
            );
        }

        $this->name = $name;
        $this->fields = $fields;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return Type
     */
    public function getType()
    {
        $fields = [];
        foreach ($this->fields as $field => $type) {
            $fields[$field] = [
                'type' => Injector::inst()->createWithArgs(
                    TypeParserInterface::class . '.string',
                    [$type]
                )->getType(),
            ];
        }

        return new ObjectType([
            'name' => $this->name,
            'fields' => $fields,
        ]);
    }
}
