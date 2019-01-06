<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\GraphQL\Storage\Encode\ResolverFactory;
use InvalidArgumentException;

abstract class CRUDResolverFactory extends ResolverFactory
{
    use Injectable;
    use Extensible;
    use DataObjectTypeTrait;

    /**
     * CRUDResolverFactory constructor.
     * @param array $context
     */
    public function __construct($context = [])
    {
        if (!isset($context['dataObjectClass'])) {
            throw new InvalidArgumentException(sprintf(
                '%s must have a dataObjectClass in its context array',
                __CLASS__
            ));
        }
        $this->setDataObjectClass($context['dataObjectClass']);
        parent::__construct($context);
    }

}