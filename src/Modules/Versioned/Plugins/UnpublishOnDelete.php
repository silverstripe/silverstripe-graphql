<?php


namespace SilverStripe\GraphQL\Modules\Versioned\Plugins;

use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Interfaces\ModelMutationPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Exception;
use Closure;
use SilverStripe\Model\ModelData;

// GraphQL dependency is optional in versioned,
// and the following implementation relies on existence of this class (in GraphQL v4)
if (!interface_exists(ModelMutationPlugin::class)) {
    return;
}

class UnpublishOnDelete implements ModelMutationPlugin
{
    const IDENTIFIER = 'unpublishOnDelete';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return UnpublishOnDelete::IDENTIFIER;
    }

    public function apply(ModelMutation $mutation, Schema $schema, array $config = []): void
    {
        $mutation->addResolverMiddleware(
            [static::class, 'unpublishOnDelete'],
            ['dataClass' => $mutation->getModel()->getSourceClass()]
        );
    }

    /**
     * @param array $context
     * @return Closure
     */
    public static function unpublishOnDelete(array $context)
    {
        $dataClass = $context['dataClass'] ?? null;
        return function ($objects, array $args, array $context) use ($dataClass) {
            if (!$dataClass) {
                return;
            }
            if (!ModelData::has_extension($dataClass, Versioned::class)) {
                return;
            }
            DB::get_conn()->withTransaction(function () use ($args, $context, $dataClass) {
                // Build list to filter
                $objects = DataList::create($dataClass)
                    ->byIDs($args['ids']);

                foreach ($objects as $object) {
                    /** @var DataObject&Versioned $object */
                    if (!$object->hasExtension(Versioned::class) || !$object->isPublished()) {
                        continue;
                    }
                    $member = UserContextProvider::get($context);
                    if (!$object->canUnpublish($member)) {
                        throw new Exception(sprintf(
                            'Cannot unpublish %s with ID %s',
                            get_class($object),
                            $object->ID
                        ));
                    }

                    $object->doUnpublish();
                }
            });
        };
    }
}
