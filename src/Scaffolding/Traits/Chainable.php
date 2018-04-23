<?php

namespace SilverStripe\GraphQL\Scaffolding\Traits;

use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;

trait Chainable
{
    /**
     * @var DataObjectScaffolder|SchemaScaffolder
     */
    protected $chainableParent;

    /**
     * Set parent
     *
     * @param DataObjectScaffolder|SchemaScaffolder $parent
     * @return $this
     */
    public function setChainableParent($parent)
    {
        $this->chainableParent = $parent;

        return $this;
    }

    /**
     * @return DataObjectScaffolder|SchemaScaffolder
     */
    public function end()
    {
        return $this->chainableParent;
    }
}
