<?php
/* @var object $scope */
/* @var \SilverStripe\GraphQL\Schema\Resolver\EncodedResolver $resolver */
?>
<?php $resolver = $scope; ?>
<?php if (!empty($resolver->getResolverMiddlewares()) || !empty($resolver->getResolverAfterwares())): ?>
    ComposedResolver::create(
    <?php echo $resolver->getExpression(); ?>,
    [
    <?php foreach ($resolver->getResolverMiddlewares() as $middleware): ?>
        <?php echo $middleware->getExpression(); ?>,
    <?php endforeach; ?>
    ],
    [
    <?php foreach ($resolver->getResolverAfterwares() as $afterware): ?>
        <?php echo $afterware->getExpression(); ?>,
    <?php endforeach; ?>
    ]
    )
<?php else: ?>
    <?php echo $resolver->getExpression(); ?>
<?php endif; ?>
