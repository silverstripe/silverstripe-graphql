<?php

namespace SilverStripe\GraphQL\Pagination;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\InternalType;
use SilverStripe\GraphQL\TypeAbstractions\ObjectTypeAbstraction;
use SilverStripe\GraphQL\TypeCreator;

/**
 * Supports offset based pagination within GraphQL.
 */
class PageInfoTypeCreator extends TypeCreator
{
    /**
     * Cached type
     *
     * @var ObjectTypeAbstraction
     */
    protected $type;

    public function toType()
    {
        if (!$this->type) {
            $this->type = parent::toType();
        }
        return $this->type;
    }

    public function attributes()
    {
        return [
            'name' => 'PageInfo',
            'description' => 'Information about pagination in a connection.',
            'fields' => $this->fields()
        ];
    }

    public function fields()
    {
        return [
            new FieldAbstraction(
                'totalCount',
                InternalType::int()->setRequired(true)
            ),
            new FieldAbstraction(
                'hasNextPage',
                InternalType::boolean()->setRequired(true)
            ),
            new FieldAbstraction(
                'hasPreviousPage',
                InternalType::boolean()->setRequired(true)
            ),
        ];
    }
}
