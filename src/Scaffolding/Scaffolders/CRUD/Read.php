<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class Read extends ListQueryScaffolder implements CRUDInterface
{
    /**
     * Read constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, null, $dataObjectClass);
        $this->setResolverFactory(ReadResolverFactory::create([
            'dataObjectClass' => $this->getDataObjectClass()
        ]));
    }


    /**
     * @return string
     */
    public function getName()
    {
        $name = parent::getName();
        if ($name) {
            return $name;
        }

        $typePlural = $this->pluralise($this->getTypeName());
        return 'read' . ucfirst($typePlural);
    }



    /**
     * Pluralise a name
     *
     * @param string $typeName
     * @return string
     */
    protected function pluralise($typeName)
    {
        // Ported from DataObject::plural_name()
        if (preg_match('/[^aeiou]y$/i', $typeName)) {
            $typeName = substr($typeName, 0, -1) . 'ie';
        }
        $typeName .= 's';
        return $typeName;
    }
}
