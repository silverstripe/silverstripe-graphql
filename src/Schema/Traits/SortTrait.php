<?php

namespace SilverStripe\GraphQL\Schema\Traits;

use GraphQL\Type\Definition\ResolveInfo;

trait SortTrait
{
    private static function getSortArgs(ResolveInfo $info, array $args, string $fieldName): array
    {
        $sortArgs = [];
        $sortOrder = self::getSortOrder($info, $fieldName);

        foreach ($sortOrder as $orderName) {
            if (!isset($args[$fieldName][$orderName])) {
                continue;
            }
            $sortArgs[$orderName] = $args[$fieldName][$orderName];
            unset($args[$fieldName][$orderName]);
        }

        return array_merge($sortArgs, $args[$fieldName]);
    }

    /**
     * Gets the original order of fields to be sorted based on the query args order.
     *
     * This is necessary because the underlying GraphQL implementation we're using ignores the
     * order of query args, and uses the order that fields are defined in the schema instead.
     */
    private static function getSortOrder(ResolveInfo $info, string $fieldName)
    {
        $relevantNode = $info->fieldDefinition->getName();

        // Find the query field node that matches the schema
        foreach ($info->fieldNodes as $node) {
            if ($node->name->value !== $relevantNode) {
                continue;
            }

            // Find the sort arg
            foreach ($node->arguments as $arg) {
                if ($arg->name->value !== $fieldName) {
                    continue;
                }

                // Get the sort order from the query
                $sortOrder = [];
                foreach ($arg->value->fields as $field) {
                    $sortOrder[] = $field->name->value;
                }
                return $sortOrder;
            }
        }

        return [];
    }
}
