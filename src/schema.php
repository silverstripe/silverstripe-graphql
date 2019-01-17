<?php

use GraphQL\Type\Definition\Type;

final class TypeRegistry_7505d64a54e061b7acd54ccd58b49dc43500b635 implements SilverStripe\GraphQL\Storage\Encode\TypeRegistryInterface
{
    private $types = array();

    /**
     * @param string $name
     * @return bool
     */
    public function hasType($name)
    {
        return method_exists($this, $name);
    }

    /**
     * @param string $name
     * @return Type|null
     */
    public function getType($name)
    {
        if (!isset($this->types[$name])) {
            $this->types[$name] = $this->{$name}();
        }
        return $this->types[$name];
    }

    private function SilverStripe_Assets_Storage_DBFile()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'SilverStripe_Assets_Storage_DBFile',
            'fields' => function () {
                return array(
                    array('name' => 'Filename', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Hash', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Variant', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'URL', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Width', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Height', 'type' => GraphQL\Type\Definition\Type::int())
                );
            }
        ));
    }

    private function AbrasiveCoatRange()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbrasiveCoatRange',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanViewType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanEditType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'URLSegment',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Title',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MenuTitle',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Content',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MetaDescription',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ExtraMeta',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInMenus',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInSearch',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Sort',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenFile',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenLink',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ReportClass',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Market',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Theory',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Bells',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readAbrasiveCoatRangesConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAbrasiveCoatRangesConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readAbrasiveCoatRangesEdge'))
                );
            }
        ));
    }

    private function readAbrasiveCoatRangesEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAbrasiveCoatRangesEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('AbrasiveCoatRange'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadAbrasiveCoatRangesSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadAbrasiveCoatRangesSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array('name' => 'field', 'type' => $this->getType('ReadAbrasiveCoatRangesSortFieldType')),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadAbrasiveCoatRangesSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadAbrasiveCoatRangesSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function SortDirection()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'SortDirection',
            'description' => 'Set order order to either ASC or DESC',
            'values' => array(
                'ASC' => array('value' => 'ASC', 'description' => 'Lowest value to highest.'),
                'DESC' => array('value' => 'DESC', 'description' => 'Highest value to lowest.')
            )
        ));
    }

    private function PageInfo()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'PageInfo',
            'description' => 'Information about pagination in a connection.',
            'fields' => function () {
                return array(
                    array('name' => 'totalCount', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'hasNextPage', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'hasPreviousPage', 'type' => GraphQL\Type\Definition\Type::boolean())
                );
            }
        ));
    }

    private function AbrasiveCoatRangeUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbrasiveCoatRangeUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Market', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Theory', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Bells', 'type' => GraphQL\Type\Definition\Type::boolean())
                );
            }
        ));
    }

    private function AbrasiveCoatRangeCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbrasiveCoatRangeCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Market', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Theory', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Bells', 'type' => GraphQL\Type\Definition\Type::boolean())
                );
            }
        ));
    }

    private function AbrasiveExchangeShade()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbrasiveExchangeShade',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanViewType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanEditType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'URLSegment',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Title',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MenuTitle',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Content',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MetaDescription',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ExtraMeta',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInMenus',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInSearch',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Sort',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenFile',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenLink',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ReportClass',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Brother',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Airplane',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readAbrasiveExchangeShadesConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAbrasiveExchangeShadesConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readAbrasiveExchangeShadesEdge'))
                );
            }
        ));
    }

    private function readAbrasiveExchangeShadesEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAbrasiveExchangeShadesEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('AbrasiveExchangeShade'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadAbrasiveExchangeShadesSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadAbrasiveExchangeShadesSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'field',
                        'type' => $this->getType('ReadAbrasiveExchangeShadesSortFieldType')
                    ),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadAbrasiveExchangeShadesSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadAbrasiveExchangeShadesSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function AbrasiveExchangeShadeUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbrasiveExchangeShadeUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Brother', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Airplane', 'type' => GraphQL\Type\Definition\Type::boolean())
                );
            }
        ));
    }

    private function AbrasiveExchangeShadeCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbrasiveExchangeShadeCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Brother', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Airplane', 'type' => GraphQL\Type\Definition\Type::boolean())
                );
            }
        ));
    }

    private function AbsentLadybug()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbsentLadybug',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanViewType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanEditType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'URLSegment',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Title',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MenuTitle',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Content',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MetaDescription',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ExtraMeta',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInMenus',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInSearch',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Sort',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenFile',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenLink',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ReportClass',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Ghost',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Pleasure',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Cave',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Sea',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Haircut',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readAbsentLadybugsConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAbsentLadybugsConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readAbsentLadybugsEdge'))
                );
            }
        ));
    }

    private function readAbsentLadybugsEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAbsentLadybugsEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('AbsentLadybug'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadAbsentLadybugsSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadAbsentLadybugsSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array('name' => 'field', 'type' => $this->getType('ReadAbsentLadybugsSortFieldType')),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadAbsentLadybugsSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadAbsentLadybugsSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function AbsentLadybugUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbsentLadybugUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Ghost', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Pleasure', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Cave', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Sea', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Haircut', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'PoisedFruitAgreementID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AbsentLadybugCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbsentLadybugCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Ghost', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Pleasure', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Cave', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Sea', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Haircut', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'PoisedFruitAgreementID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AbundantTiger()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbundantTiger',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Control',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Snakes',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readAbundantTigersConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAbundantTigersConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readAbundantTigersEdge'))
                );
            }
        ));
    }

    private function readAbundantTigersEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAbundantTigersEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('AbundantTiger'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadAbundantTigersSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadAbundantTigersSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array('name' => 'field', 'type' => $this->getType('ReadAbundantTigersSortFieldType')),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadAbundantTigersSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadAbundantTigersSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function AbundantTigerUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbundantTigerUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Control', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Snakes', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CastID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AbundantTigerCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AbundantTigerCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Control', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Snakes', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CastID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function ActuallyMittenActivity()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ActuallyMittenActivity',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanViewType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanEditType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'URLSegment',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Title',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MenuTitle',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Content',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MetaDescription',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ExtraMeta',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInMenus',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInSearch',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Sort',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenFile',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenLink',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ReportClass',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Division',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Religion',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Current',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Pigs',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Pencil',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readActuallyMittenActivitiesConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readActuallyMittenActivitiesConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readActuallyMittenActivitiesEdge'))
                );
            }
        ));
    }

    private function readActuallyMittenActivitiesEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readActuallyMittenActivitiesEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('ActuallyMittenActivity'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadActuallyMittenActivitiesSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadActuallyMittenActivitiesSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'field',
                        'type' => $this->getType('ReadActuallyMittenActivitiesSortFieldType')
                    ),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadActuallyMittenActivitiesSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadActuallyMittenActivitiesSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function ActuallyMittenActivityUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ActuallyMittenActivityUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Division', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Religion', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Current', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Pigs', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Pencil', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'BriefSparkHospitalID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'MeatySuitID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ReconditeYardID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'CuriousSparkThreadID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function ActuallyMittenActivityCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ActuallyMittenActivityCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Division', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Religion', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Current', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Pigs', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Pencil', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'BriefSparkHospitalID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'MeatySuitID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ReconditeYardID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'CuriousSparkThreadID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function Advertisement()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'Advertisement',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanViewType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanEditType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'URLSegment',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Title',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MenuTitle',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Content',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MetaDescription',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ExtraMeta',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInMenus',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInSearch',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Sort',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenFile',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenLink',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ReportClass',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Porter',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Division',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readAdvertisementsConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAdvertisementsConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readAdvertisementsEdge'))
                );
            }
        ));
    }

    private function readAdvertisementsEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAdvertisementsEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('Advertisement'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadAdvertisementsSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadAdvertisementsSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array('name' => 'field', 'type' => $this->getType('ReadAdvertisementsSortFieldType')),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadAdvertisementsSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadAdvertisementsSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function AdvertisementUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AdvertisementUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Porter', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Division', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'MightyTrainDoorID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'CutShadeID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ProfuseFairiesID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'FierceWoolID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'BrightSofaID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'TradeID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'GlisteningDropRoomID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AdvertisementCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AdvertisementCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'Porter', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Division', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'MightyTrainDoorID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'CutShadeID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ProfuseFairiesID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'FierceWoolID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'BrightSofaID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'TradeID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'GlisteningDropRoomID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AggressiveStretchPigs()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AggressiveStretchPigs',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Regret',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Station',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Chickens',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Pigs',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Cap',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Umbrella',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readAggressiveStretchPigssConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAggressiveStretchPigssConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readAggressiveStretchPigssEdge'))
                );
            }
        ));
    }

    private function readAggressiveStretchPigssEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAggressiveStretchPigssEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('AggressiveStretchPigs'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadAggressiveStretchPigssSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadAggressiveStretchPigssSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'field',
                        'type' => $this->getType('ReadAggressiveStretchPigssSortFieldType')
                    ),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadAggressiveStretchPigssSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadAggressiveStretchPigssSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function AggressiveStretchPigsUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AggressiveStretchPigsUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Regret', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Station', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Chickens', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Pigs', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Cap', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Umbrella', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'StrawID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AggressiveStretchPigsCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AggressiveStretchPigsCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Regret', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Station', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Chickens', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Pigs', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Cap', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Umbrella', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'StrawID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function Agreement()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'Agreement',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Station',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Soap',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readAgreementsConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAgreementsConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readAgreementsEdge'))
                );
            }
        ));
    }

    private function readAgreementsEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAgreementsEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('Agreement'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadAgreementsSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadAgreementsSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array('name' => 'field', 'type' => $this->getType('ReadAgreementsSortFieldType')),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadAgreementsSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadAgreementsSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function AgreementUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AgreementUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Station', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Soap', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'WoodenBulbID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'BriefSparkHospitalID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'SuddenIceTradeID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'SpotID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AgreementCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AgreementCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Station', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Soap', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'WoodenBulbID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'BriefSparkHospitalID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'SuddenIceTradeID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'SpotID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AheadFairiesBridge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AheadFairiesBridge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Beef',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Bridge',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readAheadFairiesBridgesConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAheadFairiesBridgesConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readAheadFairiesBridgesEdge'))
                );
            }
        ));
    }

    private function readAheadFairiesBridgesEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAheadFairiesBridgesEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('AheadFairiesBridge'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadAheadFairiesBridgesSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadAheadFairiesBridgesSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array('name' => 'field', 'type' => $this->getType('ReadAheadFairiesBridgesSortFieldType')),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadAheadFairiesBridgesSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadAheadFairiesBridgesSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function AheadFairiesBridgeUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AheadFairiesBridgeUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Beef', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Bridge', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LamentableFactWheelID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AheadFairiesBridgeCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AheadFairiesBridgeCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Beef', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Bridge', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LamentableFactWheelID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AheadFriend()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AheadFriend',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Quiet',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Rabbits',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Current',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Cars',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Hobbies',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readAheadFriendsConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAheadFriendsConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readAheadFriendsEdge'))
                );
            }
        ));
    }

    private function readAheadFriendsEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readAheadFriendsEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('AheadFriend'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadAheadFriendsSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadAheadFriendsSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array('name' => 'field', 'type' => $this->getType('ReadAheadFriendsSortFieldType')),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadAheadFriendsSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadAheadFriendsSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function AheadFriendUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AheadFriendUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Quiet', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Rabbits', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Current', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Cars', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Hobbies', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShortIceID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'LamentableWriterMoveID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'RightSignID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function AheadFriendCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'AheadFriendCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Quiet', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Rabbits', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Current', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Cars', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Hobbies', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShortIceID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'LamentableWriterMoveID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'RightSignID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function Page()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'Page',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanViewType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanEditType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'URLSegment',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Title',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MenuTitle',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Content',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MetaDescription',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ExtraMeta',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInMenus',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInSearch',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Sort',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenFile',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenLink',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ReportClass',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readPagesConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readPagesConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readPagesEdge'))
                );
            }
        ));
    }

    private function readPagesEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readPagesEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('Page'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadPagesSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadPagesSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array('name' => 'field', 'type' => $this->getType('ReadPagesSortFieldType')),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadPagesSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadPagesSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function PageUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'PageUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function PageCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'PageCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function PageWithDescendants()
    {
        return new \GraphQL\Type\Definition\UnionType(array(
            'name' => 'PageWithDescendants',
            'types' => (new SilverStripe\GraphQL\Storage\Encode\UnionTypeFactory(array(
                'types' => array(
                    'Page',
                    'AbrasiveCoatRange',
                    'AbrasiveExchangeShade',
                    'AbsentLadybug',
                    'ActuallyMittenActivity',
                    'Advertisement',
                    'AlikeSheep',
                    'AlikeSwing',
                    'AmbiguousDuck',
                    'AttractiveQuietHospital',
                    'Badge',
                    'BerserkQuestion',
                    'Bridge',
                    'BumpyCave',
                    'Cars',
                    'Cave',
                    'CeaselessKittens',
                    'Channel',
                    'Chickens',
                    'Coast',
                    'CoherentCaveReligion',
                    'Committee',
                    'CooperativeExistenceNight',
                    'CuddlyBoard',
                    'CurvedWashTop',
                    'CutEffectBoard',
                    'Dinosaurs',
                    'DisagreeableBeef',
                    'Ducks',
                    'DustyGhostNight',
                    'EagerHill',
                    'EfficaciousTreesHat',
                    'EliteCrib',
                    'ErectAftermathJudge',
                    'EtherealLossSpark',
                    'FamiliarReligion',
                    'FarflungChickensLegs',
                    'FierceBridgeBeef',
                    'FierceBulb',
                    'FunctionalDirt',
                    'Grip',
                    'GrotesqueJail',
                    'HappyAirplaneSwing',
                    'HelpfulAgreement',
                    'HelpfulChannelBirds',
                    'HighpitchedRegretCake',
                    'HistoricalYard',
                    'Hobbies',
                    'Holiday',
                    'HolisticSmell',
                    'HypnoticControlDoctor',
                    'Jar',
                    'JitteryUmbrella',
                    'JoblessRoad',
                    'LamentableFactWheel',
                    'LamentableWriterMove',
                    'Level',
                    'LowLadybug',
                    'MeatySuit',
                    'MeltedStoveSpark',
                    'MomentousSheepPorter',
                    'Month',
                    'PanickyBeefPull',
                    'PanickyFairiesJudge',
                    'PastEnd',
                    'Pigs',
                    'PleasantRange',
                    'Point',
                    'PoisedFruitAgreement',
                    'Popcorn',
                    'PumpedAdjustment',
                    'PumpedCarsTiger',
                    'QuarrelsomeRabbits',
                    'Range',
                    'RealHobbies',
                    'RealYard',
                    'ReflectivePoint',
                    'Religion',
                    'Sheep',
                    'ShockingFear',
                    'ShortIce',
                    'ShortScarfPoint',
                    'SpicyCoast',
                    'Spot',
                    'SqueamishPopcornPlayground',
                    'StormyAttackFriend',
                    'SturdyBabiesHospital',
                    'TabooFear',
                    'TastyBadge',
                    'TenderHate',
                    'ThoughtfulNeedCars',
                    'ThoughtlessEnd',
                    'Top',
                    'Trouble',
                    'UnadvisedSupportVeil',
                    'UnadvisedWine',
                    'UndesirableBeef',
                    'WakefulRub',
                    'Watch',
                    'WeakCoatSock',
                    'WhisperingMove',
                    'WindyJudge',
                    'WoodenTheoryScarf',
                    'WrongAftermath',
                    'SilverStripeErrorPage',
                    'SilverStripeRedirectorPage',
                    'SilverStripeVirtualPage',
                    'ThoughtlessObservation',
                    'MindlessSmoke',
                    'BrightSofa',
                    'Trade',
                    'LivelyDucksTrain',
                    'Hospital',
                    'DisturbedShow',
                    'Bells',
                    'Cushion',
                    'LeftTrain',
                    'BriefSparkHospital',
                    'UppityVest',
                    'Haircut'
                )
            )))->createClosure($this),
            'resolveType' => (new SilverStripe\GraphQL\Resolvers\UnionResolverFactory())->createClosure($this)
        ));
    }

    private function SilverStripeSiteTree()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'SilverStripeSiteTree',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanViewType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'CanEditType',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Version',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'URLSegment',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Title',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MenuTitle',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Content',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'MetaDescription',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ExtraMeta',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInMenus',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ShowInSearch',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Sort',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenFile',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'HasBrokenLink',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ReportClass',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readSilverStripeSiteTreesConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readSilverStripeSiteTreesConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readSilverStripeSiteTreesEdge'))
                );
            }
        ));
    }

    private function readSilverStripeSiteTreesEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readSilverStripeSiteTreesEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('SilverStripeSiteTree'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadSilverStripeSiteTreesSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadSilverStripeSiteTreesSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'field',
                        'type' => $this->getType('ReadSilverStripeSiteTreesSortFieldType')
                    ),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadSilverStripeSiteTreesSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadSilverStripeSiteTreesSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function SilverStripeSiteTreeUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'SilverStripeSiteTreeUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function SilverStripeSiteTreeCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'SilverStripeSiteTreeCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanViewType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'CanEditType', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Version', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'URLSegment', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Title', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MenuTitle', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Content', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'MetaDescription', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ExtraMeta', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ShowInMenus', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ShowInSearch', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'Sort', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'HasBrokenFile', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'HasBrokenLink', 'type' => GraphQL\Type\Definition\Type::boolean()),
                    array('name' => 'ReportClass', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'ParentID', 'type' => GraphQL\Type\Definition\Type::id())
                );
            }
        ));
    }

    private function SilverStripeSiteTreeWithDescendants()
    {
        return new \GraphQL\Type\Definition\UnionType(array(
            'name' => 'SilverStripeSiteTreeWithDescendants',
            'types' => (new SilverStripe\GraphQL\Storage\Encode\UnionTypeFactory(array(
                'types' => array(
                    'SilverStripeSiteTree',
                    'Page',
                    'AbrasiveCoatRange',
                    'AbrasiveExchangeShade',
                    'AbsentLadybug',
                    'ActuallyMittenActivity',
                    'Advertisement',
                    'AlikeSheep',
                    'AlikeSwing',
                    'AmbiguousDuck',
                    'AttractiveQuietHospital',
                    'Badge',
                    'BerserkQuestion',
                    'Bridge',
                    'BumpyCave',
                    'Cars',
                    'Cave',
                    'CeaselessKittens',
                    'Channel',
                    'Chickens',
                    'Coast',
                    'CoherentCaveReligion',
                    'Committee',
                    'CooperativeExistenceNight',
                    'CuddlyBoard',
                    'CurvedWashTop',
                    'CutEffectBoard',
                    'Dinosaurs',
                    'DisagreeableBeef',
                    'Ducks',
                    'DustyGhostNight',
                    'EagerHill',
                    'EfficaciousTreesHat',
                    'EliteCrib',
                    'ErectAftermathJudge',
                    'EtherealLossSpark',
                    'FamiliarReligion',
                    'FarflungChickensLegs',
                    'FierceBridgeBeef',
                    'FierceBulb',
                    'FunctionalDirt',
                    'Grip',
                    'GrotesqueJail',
                    'HappyAirplaneSwing',
                    'HelpfulAgreement',
                    'HelpfulChannelBirds',
                    'HighpitchedRegretCake',
                    'HistoricalYard',
                    'Hobbies',
                    'Holiday',
                    'HolisticSmell',
                    'HypnoticControlDoctor',
                    'Jar',
                    'JitteryUmbrella',
                    'JoblessRoad',
                    'LamentableFactWheel',
                    'LamentableWriterMove',
                    'Level',
                    'LowLadybug',
                    'MeatySuit',
                    'MeltedStoveSpark',
                    'MomentousSheepPorter',
                    'Month',
                    'PanickyBeefPull',
                    'PanickyFairiesJudge',
                    'PastEnd',
                    'Pigs',
                    'PleasantRange',
                    'Point',
                    'PoisedFruitAgreement',
                    'Popcorn',
                    'PumpedAdjustment',
                    'PumpedCarsTiger',
                    'QuarrelsomeRabbits',
                    'Range',
                    'RealHobbies',
                    'RealYard',
                    'ReflectivePoint',
                    'Religion',
                    'Sheep',
                    'ShockingFear',
                    'ShortIce',
                    'ShortScarfPoint',
                    'SpicyCoast',
                    'Spot',
                    'SqueamishPopcornPlayground',
                    'StormyAttackFriend',
                    'SturdyBabiesHospital',
                    'TabooFear',
                    'TastyBadge',
                    'TenderHate',
                    'ThoughtfulNeedCars',
                    'ThoughtlessEnd',
                    'Top',
                    'Trouble',
                    'UnadvisedSupportVeil',
                    'UnadvisedWine',
                    'UndesirableBeef',
                    'WakefulRub',
                    'Watch',
                    'WeakCoatSock',
                    'WhisperingMove',
                    'WindyJudge',
                    'WoodenTheoryScarf',
                    'WrongAftermath',
                    'SilverStripeErrorPage',
                    'SilverStripeRedirectorPage',
                    'SilverStripeVirtualPage',
                    'ThoughtlessObservation',
                    'MindlessSmoke',
                    'BrightSofa',
                    'Trade',
                    'LivelyDucksTrain',
                    'Hospital',
                    'DisturbedShow',
                    'Bells',
                    'Cushion',
                    'LeftTrain',
                    'BriefSparkHospital',
                    'UppityVest',
                    'Haircut'
                )
            )))->createClosure($this),
            'resolveType' => (new SilverStripe\GraphQL\Resolvers\UnionResolverFactory())->createClosure($this)
        ));
    }

    private function CuriousSparkThread()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'CuriousSparkThread',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::id(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'ClassName',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'LastEdited',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Created',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Regret',
                        'type' => GraphQL\Type\Definition\Type::string(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Station',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Chickens',
                        'type' => GraphQL\Type\Definition\Type::int(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    ),
                    array(
                        'name' => 'Pigs',
                        'type' => GraphQL\Type\Definition\Type::boolean(),
                        'resolve' => array('SilverStripe\\GraphQL\\Resolvers\\FieldAccessorResolver', 'resolve')
                    )
                );
            }
        ));
    }

    private function readCuriousSparkThreadsConnection()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readCuriousSparkThreadsConnection',
            'fields' => function () {
                return array(
                    array('name' => 'pageInfo', 'type' => $this->getType('PageInfo')),
                    array('name' => 'edges', 'type' => $this->getType('readCuriousSparkThreadsEdge'))
                );
            }
        ));
    }

    private function readCuriousSparkThreadsEdge()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'readCuriousSparkThreadsEdge',
            'description' => 'The collections edge',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'node',
                        'type' => $this->getType('CuriousSparkThread'),
                        'resolve' => array('SilverStripe\\GraphQL\\Pagination\\Connection', 'nodeResolver')
                    )
                );
            }
        ));
    }

    private function ReadCuriousSparkThreadsSortInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'ReadCuriousSparkThreadsSortInputType',
            'description' => 'Define the sorting',
            'fields' => function () {
                return array(
                    array('name' => 'field', 'type' => $this->getType('ReadCuriousSparkThreadsSortFieldType')),
                    array('name' => 'direction', 'type' => $this->getType('SortDirection'))
                );
            }
        ));
    }

    private function ReadCuriousSparkThreadsSortFieldType()
    {
        return new \GraphQL\Type\Definition\EnumType(array(
            'name' => 'ReadCuriousSparkThreadsSortFieldType',
            'description' => 'Field name to sort by.',
            'values' => array()
        ));
    }

    private function CuriousSparkThreadUpdateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'CuriousSparkThreadUpdateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ID', 'type' => GraphQL\Type\Definition\Type::id()),
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Regret', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Station', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Chickens', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Pigs', 'type' => GraphQL\Type\Definition\Type::boolean())
                );
            }
        ));
    }

    private function CuriousSparkThreadCreateInputType()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'CuriousSparkThreadCreateInputType',
            'fields' => function () {
                return array(
                    array('name' => 'ClassName', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'LastEdited', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Created', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Regret', 'type' => GraphQL\Type\Definition\Type::string()),
                    array('name' => 'Station', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Chickens', 'type' => GraphQL\Type\Definition\Type::int()),
                    array('name' => 'Pigs', 'type' => GraphQL\Type\Definition\Type::boolean())
                );
            }
        ));
    }

    private function CuriousSparkThreadWithDescendants()
    {
        return new \GraphQL\Type\Definition\UnionType(array(
            'name' => 'CuriousSparkThreadWithDescendants',
            'types' => (new SilverStripe\GraphQL\Storage\Encode\UnionTypeFactory(array(
                'types' => array(
                    'CuriousSparkThread',
                    'AggressiveStretchPigs'
                )
            )))->createClosure($this),
            'resolveType' => (new SilverStripe\GraphQL\Resolvers\UnionResolverFactory())->createClosure($this)
        ));
    }

    private function Query()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'Query',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'readAbrasiveCoatRanges',
                        'type' => $this->getType('readAbrasiveCoatRangesConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'AbrasiveCoatRange')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneAbrasiveCoatRange',
                        'type' => $this->getType('AbrasiveCoatRange'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'AbrasiveCoatRange')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readAbrasiveExchangeShades',
                        'type' => $this->getType('readAbrasiveExchangeShadesConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'AbrasiveExchangeShade')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneAbrasiveExchangeShade',
                        'type' => $this->getType('AbrasiveExchangeShade'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'AbrasiveExchangeShade')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readAbsentLadybugs',
                        'type' => $this->getType('readAbsentLadybugsConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'AbsentLadybug')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneAbsentLadybug',
                        'type' => $this->getType('AbsentLadybug'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'AbsentLadybug')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readAbundantTigers',
                        'type' => $this->getType('readAbundantTigersConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'AbundantTiger')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneAbundantTiger',
                        'type' => $this->getType('AbundantTiger'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'AbundantTiger')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readActuallyMittenActivities',
                        'type' => $this->getType('readActuallyMittenActivitiesConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'ActuallyMittenActivity')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneActuallyMittenActivity',
                        'type' => $this->getType('ActuallyMittenActivity'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'ActuallyMittenActivity')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readAdvertisements',
                        'type' => $this->getType('readAdvertisementsConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'Advertisement')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneAdvertisement',
                        'type' => $this->getType('Advertisement'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'Advertisement')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readAggressiveStretchPigss',
                        'type' => $this->getType('readAggressiveStretchPigssConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'AggressiveStretchPigs')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneAggressiveStretchPigs',
                        'type' => $this->getType('AggressiveStretchPigs'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'AggressiveStretchPigs')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readAgreements',
                        'type' => $this->getType('readAgreementsConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'Agreement')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneAgreement',
                        'type' => $this->getType('Agreement'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'Agreement')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readAheadFairiesBridges',
                        'type' => $this->getType('readAheadFairiesBridgesConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'AheadFairiesBridge')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneAheadFairiesBridge',
                        'type' => $this->getType('AheadFairiesBridge'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'AheadFairiesBridge')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readAheadFriends',
                        'type' => $this->getType('readAheadFriendsConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'AheadFriend')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneAheadFriend',
                        'type' => $this->getType('AheadFriend'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'AheadFriend')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readPages',
                        'type' => $this->getType('readPagesConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'Page')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOnePage',
                        'type' => $this->getType('Page'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'Page')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readSilverStripeSiteTrees',
                        'type' => $this->getType('readSilverStripeSiteTreesConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'SilverStripe\\CMS\\Model\\SiteTree')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneSilverStripeSiteTree',
                        'type' => $this->getType('SilverStripeSiteTree'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'SilverStripe\\CMS\\Model\\SiteTree')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    ),
                    array(
                        'name' => 'readCuriousSparkThreads',
                        'type' => $this->getType('readCuriousSparkThreadsConnection'),
                        'resolve' => (new SilverStripe\GraphQL\Resolvers\PaginationResolverFactory(array(
                            'parentResolver' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadResolverFactory(array('dataObjectClass' => 'CuriousSparkThread')))->createClosure(),
                            'defaultLimit' => 100,
                            'maximumLimit' => 100,
                            'sortableFields' => array()
                        )))->createClosure(),
                        'args' => array(
                            array('name' => 'limit', 'type' => GraphQL\Type\Definition\Type::int()),
                            array('name' => 'offset', 'type' => GraphQL\Type\Definition\Type::int())
                        )
                    ),
                    array(
                        'name' => 'readOneCuriousSparkThread',
                        'type' => $this->getType('CuriousSparkThread'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\ReadOneResolverFactory(array('dataObjectClass' => 'CuriousSparkThread')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'ID',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                            )
                        )
                    )
                );
            }
        ));
    }

    private function Mutation()
    {
        return new \GraphQL\Type\Definition\ObjectType(array(
            'name' => 'Mutation',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'updateAbrasiveCoatRange',
                        'type' => $this->getType('AbrasiveCoatRange'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'AbrasiveCoatRange')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AbrasiveCoatRangeUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createAbrasiveCoatRange',
                        'type' => $this->getType('AbrasiveCoatRange'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'AbrasiveCoatRange')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AbrasiveCoatRangeCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteAbrasiveCoatRange',
                        'type' => $this->getType('AbrasiveCoatRange'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'AbrasiveCoatRange')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateAbrasiveExchangeShade',
                        'type' => $this->getType('AbrasiveExchangeShade'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'AbrasiveExchangeShade')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AbrasiveExchangeShadeUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createAbrasiveExchangeShade',
                        'type' => $this->getType('AbrasiveExchangeShade'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'AbrasiveExchangeShade')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AbrasiveExchangeShadeCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteAbrasiveExchangeShade',
                        'type' => $this->getType('AbrasiveExchangeShade'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'AbrasiveExchangeShade')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateAbsentLadybug',
                        'type' => $this->getType('AbsentLadybug'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'AbsentLadybug')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AbsentLadybugUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createAbsentLadybug',
                        'type' => $this->getType('AbsentLadybug'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'AbsentLadybug')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AbsentLadybugCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteAbsentLadybug',
                        'type' => $this->getType('AbsentLadybug'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'AbsentLadybug')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateAbundantTiger',
                        'type' => $this->getType('AbundantTiger'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'AbundantTiger')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AbundantTigerUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createAbundantTiger',
                        'type' => $this->getType('AbundantTiger'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'AbundantTiger')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AbundantTigerCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteAbundantTiger',
                        'type' => $this->getType('AbundantTiger'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'AbundantTiger')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateActuallyMittenActivity',
                        'type' => $this->getType('ActuallyMittenActivity'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'ActuallyMittenActivity')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('ActuallyMittenActivityUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createActuallyMittenActivity',
                        'type' => $this->getType('ActuallyMittenActivity'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'ActuallyMittenActivity')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('ActuallyMittenActivityCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteActuallyMittenActivity',
                        'type' => $this->getType('ActuallyMittenActivity'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'ActuallyMittenActivity')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateAdvertisement',
                        'type' => $this->getType('Advertisement'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'Advertisement')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AdvertisementUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createAdvertisement',
                        'type' => $this->getType('Advertisement'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'Advertisement')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AdvertisementCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteAdvertisement',
                        'type' => $this->getType('Advertisement'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'Advertisement')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateAggressiveStretchPigs',
                        'type' => $this->getType('AggressiveStretchPigs'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'AggressiveStretchPigs')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AggressiveStretchPigsUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createAggressiveStretchPigs',
                        'type' => $this->getType('AggressiveStretchPigs'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'AggressiveStretchPigs')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AggressiveStretchPigsCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteAggressiveStretchPigs',
                        'type' => $this->getType('AggressiveStretchPigs'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'AggressiveStretchPigs')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateAgreement',
                        'type' => $this->getType('Agreement'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'Agreement')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AgreementUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createAgreement',
                        'type' => $this->getType('Agreement'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'Agreement')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AgreementCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteAgreement',
                        'type' => $this->getType('Agreement'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'Agreement')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateAheadFairiesBridge',
                        'type' => $this->getType('AheadFairiesBridge'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'AheadFairiesBridge')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AheadFairiesBridgeUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createAheadFairiesBridge',
                        'type' => $this->getType('AheadFairiesBridge'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'AheadFairiesBridge')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AheadFairiesBridgeCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteAheadFairiesBridge',
                        'type' => $this->getType('AheadFairiesBridge'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'AheadFairiesBridge')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateAheadFriend',
                        'type' => $this->getType('AheadFriend'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'AheadFriend')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AheadFriendUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createAheadFriend',
                        'type' => $this->getType('AheadFriend'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'AheadFriend')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('AheadFriendCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteAheadFriend',
                        'type' => $this->getType('AheadFriend'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'AheadFriend')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updatePage',
                        'type' => $this->getType('Page'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'Page')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('PageUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createPage',
                        'type' => $this->getType('Page'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'Page')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('PageCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deletePage',
                        'type' => $this->getType('Page'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'Page')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateSilverStripeSiteTree',
                        'type' => $this->getType('SilverStripeSiteTree'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'SilverStripe\\CMS\\Model\\SiteTree')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('SilverStripeSiteTreeUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createSilverStripeSiteTree',
                        'type' => $this->getType('SilverStripeSiteTree'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'SilverStripe\\CMS\\Model\\SiteTree')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('SilverStripeSiteTreeCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteSilverStripeSiteTree',
                        'type' => $this->getType('SilverStripeSiteTree'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'SilverStripe\\CMS\\Model\\SiteTree')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    ),
                    array(
                        'name' => 'updateCuriousSparkThread',
                        'type' => $this->getType('CuriousSparkThread'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\UpdateResolverFactory(array('dataObjectClass' => 'CuriousSparkThread')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('CuriousSparkThreadUpdateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'createCuriousSparkThread',
                        'type' => $this->getType('CuriousSparkThread'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\CreateResolverFactory(array('dataObjectClass' => 'CuriousSparkThread')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'Input',
                                'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('CuriousSparkThreadCreateInputType'))
                            )
                        )
                    ),
                    array(
                        'name' => 'deleteCuriousSparkThread',
                        'type' => $this->getType('CuriousSparkThread'),
                        'resolve' => (new SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories\DeleteResolverFactory(array('dataObjectClass' => 'CuriousSparkThread')))->createClosure(),
                        'args' => array(
                            array(
                                'name' => 'IDs',
                                'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::listOf(GraphQL\Type\Definition\Type::id()))
                            )
                        )
                    )
                );
            }
        ));
    }
}