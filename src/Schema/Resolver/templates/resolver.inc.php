<?php
/* @var object $scope */
/* @var \SilverStripe\GraphQL\Schema\Resolver\EncodedResolver $resolver */
?>
<?php $resolver = $scope; ?>
    ComposedResolver::create([
    <?php foreach ($resolver->getStack() as $function) : ?>
        <?=$function->getExpression() ?>,
    <?php endforeach; ?>
    ])
