<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use Exception;
use SilverStripe\ORM\DataList;
use Psr\Container\NotFoundExceptionInterface;

class ReadOneResolverFactory extends CRUDResolverFactory implements ResolverFactory
{
    /**
     * @return \Closure
     * @throws NotFoundExceptionInterface
     */
    public function createResolver()
    {
        $class = $this->getDataObjectClass();
        $singleton = $this->getDataObjectInstance();
        return function ($object, array $args, $context, ResolveInfo $info) use ($class, $singleton) {
            if (!$singleton->canView($context['currentUser'])) {
                throw new Exception(sprintf(
                    'Cannot view %s',
                    $class
                ));
            }
            // get as a list so extensions can influence it pre-query
            $list = DataList::create($class)
                ->filter('ID', $args['ID']);
            $this->extend('updateList', $list, $args, $context, $info);

            return $list->first();
        };
    }
}