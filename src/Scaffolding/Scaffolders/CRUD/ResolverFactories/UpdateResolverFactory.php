<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\ORM\DataList;
use Psr\Container\NotFoundExceptionInterface;
use Exception;
use Closure;

class UpdateResolverFactory extends CRUDResolverFactory implements ResolverFactory
{
    /**
     * @return Closure
     * @throws NotFoundExceptionInterface
     */
    public function createResolver()
    {
        $class = $this->getDataObjectClass();
        $singleton = $this->getDataObjectInstance();

        return function ($object, array $args, $context, ResolveInfo $info) use ($class, $singleton) {
            $input = $args['Input'];
            $obj = DataList::create($class)
                ->byID($input['ID']);
            if (!$obj) {
                throw new Exception(sprintf(
                    '%s with ID %s not found',
                    $class,
                    $input['ID']
                ));
            }
            unset($input['ID']);
            if (!$obj->canEdit($context['currentUser'])) {
                throw new Exception(sprintf(
                    'Cannot edit this %s',
                    $class
                ));
            }

            // Extension points that return false should kill the write operation
            $results = $this->extend('augmentMutation', $obj, $args, $context, $info);
            if (in_array(false, $results, true)) {
                return $obj;
            }

            $obj->update($input);
            $obj->write();

            return $obj;
        };
    }
}