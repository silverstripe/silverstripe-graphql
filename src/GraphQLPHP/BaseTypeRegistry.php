<?php


namespace SilverStripe\GraphQL\GraphQLPHP;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ExtensibleTypeRegistryInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface;

abstract class BaseTypeRegistry implements ExtensibleTypeRegistryInterface
{
    /**
     * @var array
     */
    protected $types = [];

    /**
     * @var TypeRegistryInterface[]
     */
    protected $extensions = [];

    /**
     * @param string $name
     * @return bool
     */
    public function hasType($name)
    {
        if (isset($this->types[$name]) || method_exists($this, $name)) {
            return true;
        }
        foreach ($this->extensions as $registry) {
            if ($registry->hasType($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $name
     * @return Type|null
     */
    public function getType($name)
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        if (method_exists($this, $name)) {
            $this->types[$name] = $this->{$name}();

            return $this->types[$name];
        } else {
            foreach ($this->extensions as $registry) {
                if ($registry->hasType($name)) {
                    return $this->types[$name];
                }
            }
        }

        return null;
    }

    /**
     * @param TypeRegistryInterface $registry
     * @return $this
     */
    public function addExtension(TypeRegistryInterface $registry)
    {
        $this->extensions[get_class($registry)] = $registry;

        return $this;
    }
}
