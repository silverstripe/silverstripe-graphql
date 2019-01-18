<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\InternalType;
use SilverStripe\GraphQL\Schema\Components\FieldCollection;
use SilverStripe\GraphQL\TypeCreator;

/**
 * Supports offset based pagination within GraphQL.
 */
class PageInfoTypeCreator extends TypeCreator
{
    /**
     * Cached type
     *
     * @var FieldCollection
     */
    protected $type;

    public function toType()
    {
        if (!$this->type) {
            $this->type = parent::toType();
        }
        return $this->type;
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'PageInfo',
            'description' => 'Information about pagination in a connection.',
            'fields' => $this->fields()
        ];
    }

    /**
     * @return array
     */
    public function fields()
    {
        return [
            Field::create(
                'totalCount',
                InternalType::int()->setRequired(true)
            ),
            Field::create(
                'hasNextPage',
                InternalType::boolean()->setRequired(true)
            ),
            Field::create(
                'hasPreviousPage',
                InternalType::boolean()->setRequired(true)
            ),
        ];
    }
}
