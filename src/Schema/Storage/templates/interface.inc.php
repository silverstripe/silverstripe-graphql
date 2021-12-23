<?php
/* @var object $scope */
/* @var \SilverStripe\GraphQL\Schema\Type\Interface $interface */
/* @var array $globals */
?>
<?php $interface = $scope; ?>
namespace <?=$globals['namespace'] ?>;

use GraphQL\Type\Definition\InterfaceType;
use SilverStripe\GraphQL\Schema\Resolver\ComposedResolver;

// @type:<?=$interface->getName(); ?>

class <?=$globals['obfuscator']->obfuscate($interface->getName()) ?> extends InterfaceType
{
    public function __construct()
    {
        $resolver = <?=$interface->getEncodedTypeResolver()->encode(); ?>;
        parent::__construct([
            'name' => '<?=$interface->getName(); ?>',
            'resolveType' => function (...$args) use ($resolver) {
                $type = call_user_func_array($resolver->toClosure(), $args);
                return call_user_func([__NAMESPACE__ . '\\<?=$globals['typeClassName']; ?>', $type]);
            },
        <?php if (!empty($interface->getDescription())) : ?>
            'description' => '<?=addslashes($interface->getDescription()); ?>',
        <?php endif; ?>
        <?php if (!empty($interface->getInterfaces())) : ?>
            'interfaces' => function () {
                return array_map(function ($interface) {
                    return call_user_func([__NAMESPACE__ . '\\<?=$globals['typeClassName']; ?>', $interface]);
                }, <?=$interface->getEncodedInterfaces(); ?>);
            },
        <?php endif; ?>
            'fields' => function () {
                return [
                <?php foreach ($interface->getFields() as $field) : ?>
                    [
                        'name' => '<?=$field->getName(); ?>',
                        'type' => <?=$field->getEncodedType()->encode() ?>,
                    <?php if (!empty($field->getDescription())) : ?>
                        'description' => '<?=addslashes($field->getDescription()); ?>',
                    <?php endif; ?>
                    <?php if (!empty($field->getArgs())) : ?>
                        'args' => [
                        <?php foreach ($field->getArgs() as $arg) : ?>
                            [
                                'name' => '<?=$arg->getName(); ?>',
                                'type' => <?=$arg->getEncodedType()->encode(); ?>,
                            <?php if ($arg->getDefaultValue() !== null) : ?>
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
