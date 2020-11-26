<?php
/* @var object $scope */
/* @var array $globals */
?>
<?php $components = $scope; ?>
namespace <?=$globals['namespace'] ?>;

use SilverStripe\GraphQL\Schema\Storage\AbstractTypeRegistry;

class <?=$globals['typeClassName']; ?> extends AbstractTypeRegistry
{
    protected static $types = [];

    protected static function getSourceDirectory(): string
    {
        return __DIR__;
    }

    protected static function getSourceNamespace(): string
    {
        return __NAMESPACE__;
    }

    <?php foreach ($components as $component) : ?>
public static function <?=$component->getName(); ?>() { return static::get('<?=$component->getName(); ?>'); }
    <?php endforeach; ?>

}
