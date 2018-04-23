<?php

namespace MyProject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Member;
use SilverStripe\Assets\File;

/**
 * @method Member Author()
 * @method File[]|ManyManyList Files()
 * @method Comment[]|HasManyList Comments()
 */
class Post extends DataObject implements ScaffoldingProvider
{

    private static $db = [
        'Title' => 'Varchar',
        'Content' => 'HTMLText',
        'Date' => 'Datetime'
    ];

    private static $has_one = [
        'Author' => Member::class
    ];

    private static $many_many = [
        'Files' => File::class
    ];

    private static $has_many = [
        'Comments' => Comment::class
    ];

    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        $scaffolder
            ->type(Post::class)
                ->addFields(['ID', 'Title', 'Content', 'Author', 'Date'])
                // basic many_many nested query, no options
                ->nestedQuery('Files')
                    ->end()
                // more complex nested query
                ->nestedQuery('Comments')
                    ->addArgs([
                        'Today' => 'Boolean'
                    ])
                    ->addSortableFields(['Author'])
                    ->setResolver(function ($object, array $args, $context, ResolveInfo $info) {
                        /** @var Post $object */
                        $comments = $object->Comments();
                        if (isset($args['Today']) && $args['Today']) {
                            $comments = $comments->where('DATE(Created) = DATE(NOW())');
                        }

                        return $comments;
                    })
                    ->end()
                // basic crud operation, no options
                ->operation(SchemaScaffolder::CREATE)
                    ->end()
                // complex crud operation, with custom args
                ->operation(SchemaScaffolder::READ)
                    ->addArgs([
                        'StartingWith' => 'String'
                    ])
                    ->setResolver(function ($obj, $args) {
                        $list = Post::get();
                        if (isset($args['StartingWith'])) {
                            $list = $list->filter('Title:StartsWith', $args['StartingWith']);
                        }

                        return $list;
                    })
                    ->end()
                ->end()
            // these types were all created implicitly above. Add some fields to them.
            ->type(Member::class)
                ->addFields(['Name', 'FirstName', 'Surname', 'Email'])
                ->end()
            ->type(File::class)
                ->addAllFieldsExcept(['Content'])
                ->addFields(['File'])
                ->end()
            ->type(Comment::class)
                ->addFields(['Comment', 'Author'])
                ->end()
            // Arbitrary mutation
            ->mutation('updatePostTitle', Post::class)
                ->addArgs([
                    'ID' => 'ID!',
                    'NewTitle' => 'String!'
                ])
                ->setResolver(function ($obj, $args) {
                    $post = Post::get()->byID($args['ID']);
                    if ($post->canEdit()) {
                        $post->Title = $args['NewTitle'];
                        $post->write();
                    }

                    return $post;
                })
                ->end()
            // Arbitrary query
            ->query('latestPost', Post::class)
                ->setUsePagination(false)
                ->setResolver(function ($obj, $args) {
                    return Post::get()->sort('Date', 'DESC')->first();
                })
                ->end()
            ->type('SilverStripe\\CMS\\Model\\RedirectorPage')
                ->addFields(['ExternalURL', 'Content'])
                ->operation(SchemaScaffolder::READ)
                    ->end()
                ->operation(SchemaScaffolder::CREATE)
                    ->end()
                ->end();


        return $scaffolder;
    }

    public function canView($member = null, $context = [])
    {
        return true;
    }

    public function canEdit($member = null, $context = [])
    {
        return true;
    }

    public function canCreate($member = null, $context = [])
    {
        return true;
    }

    public function canDelete($member = null, $context = [])
    {
        return true;
    }
}
