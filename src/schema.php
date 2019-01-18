<?php

use GraphQL\Type\Definition\Type;

final class TypeRegistry_7505d64a54e061b7acd54ccd58b49dc43500b635 implements \SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface
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
                    array(
                        'name' => 'pageInfo',
                        'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('PageInfo'))
                    ),
                    array(
                        'name' => 'edges',
                        'type' => GraphQL\Type\Definition\Type::listOf($this->getType('readAbrasiveCoatRangesEdge'))
                    )
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
                    array(
                        'name' => 'totalCount',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::int())
                    ),
                    array(
                        'name' => 'hasNextPage',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::boolean())
                    ),
                    array(
                        'name' => 'hasPreviousPage',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::boolean())
                    )
                );
            }
        ));
    }

    private function AbrasiveCoatRangeUpdateInputType()
    {
        return new \GraphQL\Type\Definition\InputObjectType(array(
            'name' => 'AbrasiveCoatRangeUpdateInputType',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                    ),
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
                    array(
                        'name' => 'pageInfo',
                        'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('PageInfo'))
                    ),
                    array(
                        'name' => 'edges',
                        'type' => GraphQL\Type\Definition\Type::listOf($this->getType('readAbrasiveExchangeShadesEdge'))
                    )
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
            'name' => 'AbrasiveExchangeShadeUpdateInputType',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                    ),
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
                    array(
                        'name' => 'pageInfo',
                        'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('PageInfo'))
                    ),
                    array(
                        'name' => 'edges',
                        'type' => GraphQL\Type\Definition\Type::listOf($this->getType('readAbsentLadybugsEdge'))
                    )
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
            'name' => 'AbsentLadybugUpdateInputType',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                    ),
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
                    array(
                        'name' => 'pageInfo',
                        'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('PageInfo'))
                    ),
                    array(
                        'name' => 'edges',
                        'type' => GraphQL\Type\Definition\Type::listOf($this->getType('readAbundantTigersEdge'))
                    )
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
            'name' => 'AbundantTigerUpdateInputType',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                    ),
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
                    array(
                        'name' => 'pageInfo',
                        'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('PageInfo'))
                    ),
                    array(
                        'name' => 'edges',
                        'type' => GraphQL\Type\Definition\Type::listOf($this->getType('readActuallyMittenActivitiesEdge'))
                    )
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
            'name' => 'ActuallyMittenActivityUpdateInputType',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                    ),
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
                    array(
                        'name' => 'pageInfo',
                        'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('PageInfo'))
                    ),
                    array(
                        'name' => 'edges',
                        'type' => GraphQL\Type\Definition\Type::listOf($this->getType('readPagesEdge'))
                    )
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
            'name' => 'PageUpdateInputType',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                    ),
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
            'types' => (new \SilverStripe\GraphQL\Schema\Encoding\Factories\UnionTypeFactory(array(
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
                    array(
                        'name' => 'pageInfo',
                        'type' => GraphQL\Type\Definition\Type::nonNull($this->getType('PageInfo'))
                    ),
                    array(
                        'name' => 'edges',
                        'type' => GraphQL\Type\Definition\Type::listOf($this->getType('readSilverStripeSiteTreesEdge'))
                    )
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
            'name' => 'SilverStripeSiteTreeUpdateInputType',
            'fields' => function () {
                return array(
                    array(
                        'name' => 'ID',
                        'type' => GraphQL\Type\Definition\Type::nonNull(GraphQL\Type\Definition\Type::id())
                    ),
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
        return new \GraphQL\Type\Definition\InputObjectType(array(
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
            'types' => (new \SilverStripe\GraphQL\Schema\Encoding\Factories\UnionTypeFactory(array(
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
                    )
                );
            }
        ));
    }
}
