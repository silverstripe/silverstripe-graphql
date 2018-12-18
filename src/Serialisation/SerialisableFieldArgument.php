<?php

namespace SilverStripe\GraphQL\Serialisation;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use Closure;

class SerialisableFieldArgument extends FieldArgument implements TypeStoreConsumer
{
    /**
     * @var InputType
     */
    protected $type;

    /**
     * @var bool
     */
    protected $defaultValueExists = false;

    /**
     * @var bool
     */
    protected $pure = false;

    /**
     * @var callable
     */
    protected $typeCreator;

    /**
     * SerialisableFieldArgument constructor.
     * @param $def
     * @throws Error
     */
    public function __construct($def)
    {
        parent::__construct($def);
        // Overload private property
        $this->type = $def['type'];

        if (isset($def['defaultValue'])) {
            $this->defaultValue = $def['defaultValue'];
            $this->defaultValueExists = true;
        }
        if (isset($def['pure'])) {
            $this->pure = (bool) $def['pure'];
        }
        if (isset($def['typeCreator'])) {
            $this->typeCreator = $def['typeCreator'];
        }
    }

    /**
     * This wouldn't have to be overloaded if the parent class didn't use `new self()`!
     * @param array $config
     * @return array
     * @throws Error
     */
    public static function createMap(array $config)
    {
        $map = [];
        foreach ($config as $name => $argConfig) {
            if (!is_array($argConfig)) {
                $argConfig = ['type' => $argConfig];
            }
            $map[] = new static($argConfig + ['name' => $name]);
        }
        return $map;
    }

    /**
     * @param TypeStoreInterface $typeStore
     * @throws NotFoundExceptionInterface
     */
    public function loadFromTypeStore(TypeStoreInterface $typeStore)
    {
        /* @var TypeSerialiser $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);
        if (!$this->type instanceof Type) {
            $typeCreator = $serialiser->getTypeCreator($this->getType());
            $this->type = $typeCreator($typeStore);
        }
    }

    /**
     * @return InputType
     */
    public function getType()
    {
        if ($this->type instanceof Type) {
            return $this->type;
        }
        if ($this->typeCreator) {
            $this->type = call_user_func($this->typeCreator);
        }
        return $this->type;
    }

    /**
     * @return bool
     */
    public function defaultValueExists()
    {
        return $this->defaultValueExists;
    }

    /**
     * @throws Error
     */
    protected function assertSerialisable()
    {
        Utils::invariant(
            !$this->astNode,
            'Field argument %s is not serialisable because it has an astNode property assigned',
            $this->name
        );
        Utils::invariant(
            !$this->typeCreator || !$this->typeCreator instanceof Closure,
            'typeCreator must use the callable array syntax. Closures are not allowed'
        );

    }

    /**
     * @throws Error
     * @throws NotFoundExceptionInterface
     */
    public function __sleep()
    {
        $this->assertSerialisable();
        /* @var TypeSerialiser $serialiser */
        $serialiser = Injector::inst()->get(TypeSerialiserInterface::class);

        // If the type is "pure" we can assume there will only be one instance of it,
        // and we do not have to guarantee a singleton.
        if ($this->pure) {
            $this->type = $this->getType();
        } else {
            $this->type = $serialiser->serialiseType($this->getType());
        }

        return [
            'type',
            'name',
            'description',
            'defaultValue',
            'defaultValueExists',
            'pure',
            'typeCreator',
        ];
    }

}