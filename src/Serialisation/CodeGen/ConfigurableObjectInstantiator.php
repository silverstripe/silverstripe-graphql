<?php

namespace SilverStripe\GraphQL\Serialisation\CodeGen;

class ConfigurableObjectInstantiator
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var array
     */
    protected $configArray;

    /**
     * @var string
     */
    protected $varName;

    /**
     * @var int
     */
    protected $tabLevel;

    /**
     * ConfigurableObjectInstantiator constructor.
     * @param $className
     * @param $configArray
     * @param string|null $varName
     */
    public function __construct($className, $configArray, $varName = null, $tabLevel = 1)
    {
        $this->className = $className;
        $this->configArray = $configArray;
        $this->varName = $varName;
        $this->tabLevel = $tabLevel;

    }

    /**
     * @return string
     */
    public function __toString()
    {
        $config = new ArrayDefinition($this->configArray, $this->tabLevel);
        $var = $this->varName ? sprintf('$%s = ', $this->varName) : '';

        return sprintf('%snew %s(%s)', $var, $this->className, $config);
    }
}