<?php

namespace SilverStripe\GraphQL\Scaffolding\Traits;

trait Chainable
{
	protected $chainableParent;

	public function setChainableParent($parent)
	{
		$this->chainableParent = $parent;

		return $this;
	}

	public function end()
	{
		return $this->chainableParent;
	}
}