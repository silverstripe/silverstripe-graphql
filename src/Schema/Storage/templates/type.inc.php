<?php
/* @var object $scope */
/* @var \SilverStripe\GraphQL\Schema\Type\Type $type */
/* @var array $globals */
?>
<?php $type = $scope; ?>
namespace <?=$globals['namespace'] ?>;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\GraphQL\Schema\Resolver\ComposedResolver;


class <?=$type->getName() ?> extends <?php if($type->getIsInput()): ?>InputObjectType<?php else: ?>ObjectType<?php endif; ?>
{
    public function __construct()
    {
        parent::__construct([
            'name' => '<?=$type->getName() ?>',
        <?php if(!empty($type->getDescription())): ?>
            'description' => '<?=addslashes($type->getDescription()); ?>',
        <?php endif; ?>
        <?php if (!empty($type->getInterfaces())): ?>
            'interfaces' => function () {
                return array_map(function ($interface) {
                    return call_user_func([__NAMESPACE__ . '\\<?=$globals['typeClassName']; ?>', $interface]);
                }, <?=$type->getEncodedInterfaces(); ?>);
            },
        <?php endif; ?>
'fields' => function () {
                return [
                <?php foreach($type->getFields() as $field): ?>
                    [
                        'name' => '<?=$field->getName(); ?>',
                        'type' => <?=$field->getEncodedType()->encode() ?>,
                        'resolve' => <?=$field->getEncodedResolver($type->getName())->encode(); ?>,
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
                                'defaultValue' => <?=var_export($arg->getDefaultValue(), true); ?>,
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
