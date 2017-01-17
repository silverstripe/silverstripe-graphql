<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\Type;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Interfaces\Configurable;
use SilverStripe\GraphQL\Scaffolding\Util\TypeParser;
use Exception;

class ArgumentScaffolder implements Configurable
{

	/**
	 * @var string
	 */
	public $argName;

	/**	 
	 * @var string
	 */
	protected $description;

	/**
	 * @var GraphQL\Definition\Type\Type;
	 */
	protected $type;

	/**
	 * @var scalar
	 */
	protected $defaultValue;

	/**
	 * @var  boolean
	 */
	protected $required;

	/**
	 * ArgumentScaffolder constructor
	 * @param string $argName      Name of the argument
	 * @param strint $typeStr      A string describing the type (see TypeParser)
	 */
	public function __construct($argName, $typeStr)
	{
		$this->argName = $argName;
		
		$parser = new TypeParser($typeStr);
		$this->defaultValue = $parser->getDefaultValue();
		$this->type = $parser->getType();
		$this->required = $parser->isRequired();
	}

	/**
	 * Sets the argument as required
	 * @param boolean $bool
	 */
	public function setRequired($bool)
	{
		$this->required = (boolean) $bool;

		return $this;
	}

	/**
	 * Sets the argument description
	 * @param string $description 
	 */
	public function setDescription($description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * Sets the default value of the argument
	 * @param mixed $value
	 */
	public function setDefaultValue($value)
	{
		$this->defaultValue = $value;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @return scalar
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @return boolean
	 */
	public function isRequired()
	{
		return $this->required;
	}

	/**
	 * Applies an array of configuration to the scaffolder
	 * @param  array  $config 
	 * @return void
	 */
	public function applyConfig(array $config)
	{
		if(isset($config['description'])) {
			$this->description = $config['description'];
		}

		if(isset($config['default'])) {
			$this->defaultValue = $config['default'];
		}

		if(isset($config['required'])) {
			$this->required = (boolean) $config['required'];
		}
	}

	/**
	 * Creates an array suitable for a map of args in a field
	 * @return array
	 */
	public function toArray()
	{
		return [
			'description' => $this->description,
			'type' => $this->required ? Type::nonNull($this->type) : $this->type,
			'defaultValue' => $this->defaultValue
		];
	}

}