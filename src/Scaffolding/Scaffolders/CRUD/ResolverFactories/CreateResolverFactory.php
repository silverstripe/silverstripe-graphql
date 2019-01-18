<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ResolverFactories;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Injector\Injector;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\ORM\DataObject;
use Exception;
use Closure;

class CreateResolverFactory extends CRUDResolverFactory
{
    /**
     * @return callable|Closure
     * @throws NotFoundExceptionInterface
     */
    public function createClosure()
    {
        $class = $this->getDataObjectClass();
        $singleton = $this->getDataObjectInstance();

        return function ($object, array $args, $context, ResolveInfo $info) use ($class, $singleton) {
            // Todo: this is totally half baked
            if (!$singleton->canCreate($context['currentUser'], $context)) {
                throw new Exception("Cannot create {$class}");
            }

            /** @var DataObject $newObject */
            $newObject = Injector::inst()->create($class);
            $newObject->update($args['Input']);

            // Extension points that return false should kill the create
            $results = $this->extend('augmentMutation', $newObject, $args, $context, $info);
            if (in_array(false, $results, true)) {
                return null;
            }

            // Save and return
            $newObject->write();
            return DataObject::get_by_id($class, $newObject->ID);
        };
    }
}
