<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\GraphQL\Schema\Components\Enum;
use SilverStripe\GraphQL\TypeCreator;

/**
 * Type for specifying the sort direction for a specific field.
 *
 * @see SortInputTypeCreator
 */
class SortDirectionTypeCreator extends TypeCreator
{
    /**
     * @var Enum
     */
    protected $type;

    protected $inputObject = true;

    public function toType()
    {
        if (!$this->type) {
            $this->type = new Enum(
                'SortDirection',
                'Set order order to either ASC or DESC',
                [
                    'ASC' => [
                        'value' => 'ASC',
                        'description' => 'Lowest value to highest.',
                    ],
                    'DESC' => [
                        'value' => 'DESC',
                        'description' => 'Highest value to lowest.',
                    ],
                ]
            );
        }
        return $this->type;
    }
    
}
