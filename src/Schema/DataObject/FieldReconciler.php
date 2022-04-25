<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;

trait FieldReconciler
{
    /**
     * @param array $config
     * @param ModelType $modelType
     * @return array
     * @throws SchemaBuilderException
     */
    private function reconcileFields(
        array $config,
        ModelType $modelType
    ): array {
        $configFields = $config['fields'] ?? null;
        $fields = [];
        if ($configFields) {
            Schema::assertValidConfig($configFields);
            foreach ($configFields as $fieldName => $bool) {
                if ($bool === false) {
                    $fields = array_filter($fields ?? [], function ($field) use ($fieldName) {
                        return $field !== $fieldName;
                    });
                } elseif ($fieldName === Schema::ALL) {
                    $fields = array_merge($fields, array_keys($modelType->getFields() ?? []));
                } else {
                    $fields[] = $fieldName;
                }
            }
        } else {
            $fields = array_keys($modelType->getFields() ?? []);
        }

        return $fields;
    }
}
