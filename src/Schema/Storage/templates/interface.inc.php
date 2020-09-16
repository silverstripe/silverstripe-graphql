<?php
/* @var object $scope */
/* @var \SilverStripe\GraphQL\Schema\Type\Interface $interface */
/* @var array $globals */
?>
<?php $interface = $scope; ?>
namespace <?=$globals['namespace'] ?>;

use GraphQL\Type\Definition\InterfaceType;

class <?=$interface->getName(); ?> extends InterfaceType
{
    public function __construct()
    {
        parent::__construct([
            'name' => '<?=$interface->getName(); ?>',
            'resolveType' => function ($obj) {
                $type = call_user_func_array(<?=$interface->getEncodedResolver()->encode(); ?>, [$obj]);
                return call_user_func([__NAMESPACE__ . '\\<?=$globals['typeClassName']; ?>', $type]);
            },
        <?php if (!empty($interface->getDescription())): ?>
            'description' => '<?=addslashes($interface->getDescription()); ?>',
        <?php endif; ?>
            'fields' => function () {
                return [
                <?php foreach($interface->getFields() as $field): ?>
                    [
                        'name' => '<?=$field->getName(); ?>',
                        'type' => <?=$field->getEncodedType()->encode() ?>,
                    <?php if (!empty($field->getDescription())): ?>
                        'description' => '<?=addslashes($field->getDescription()); ?>',
                    <?php endif; ?>
                    <?php if (!empty($field->getArgs())): ?>
                        'args' => [
                        <?php foreach ($field->getArgs() as $arg): ?>
                            [
                                'name' => '<?=$arg->getName(); ?>',
                                'type' => <?=$arg->getEncodedType()->encode(); ?>,
                            <?php if ($arg->getDefaultValue() !== null): ?>
                                'defaultValue' => <?=$arg->getDefaultValue(); ?>,
                            <?php endif; ?>
                            ],
                        <?php endforeach; ?>
                        ],
                    <?php endif; ?>
                    ],
                <?php endforeach; ?>
                ];
            },
        ]);
    }
}
