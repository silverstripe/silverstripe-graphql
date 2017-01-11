# SilverStripe GraphQL Server

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-graphql.svg?branch=master)](https://travis-ci.org/silverstripe/silverstripe-graphql)
[![codecov](https://codecov.io/gh/silverstripe/silverstripe-graphql/branch/master/graph/badge.svg)](https://codecov.io/gh/silverstripe/silverstripe-graphql)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/silverstripe/silverstripe-graphql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/silverstripe/silverstripe-graphql/?branch=master)

This modules serves SilverStripe data as
[GraphQL](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html)
representations, with helpers to generate schemas based on SilverStripe model
introspection. It layers a pluggable schema registration system on top of the
[graphql-php](https://github.com/webonyx/graphql-php) library. The APIs are
very similar, for example:

## Installation

Require the [composer](http://getcomposer.org) package in your `composer.json`

```
composer require silverstripe/graphql
```

## Usage

GraphQL is used through a single route which defaults to `/graphql`. You need
to define *Types* and *Queries* to expose your data via this endpoint.

Currently, the default endpoint (`/graphql`) is protected against access unless the
current user has CMS Access.

## Examples

Code examples can be found in the `examples/` folder (built out from the
configuration docs below).

## Configuration

### Define Types

Types describe your data. While your data could be any arbitrary structure, in
a SilverStripe project a GraphQL type usually relates to a `DataObject`.
GraphQL uses this information to validate queries and allow GraphQL clients to
introspect your API capabilities. The GraphQL type system is hierarchical, so
the `fields()` definition declares object properties as scalar types within
your complex type. Refer to the
[graphql-php type definitions](https://github.com/webonyx/graphql-php#type-system)
for available types.

```php
<?php

namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\TypeCreator;
use SilverStripe\GraphQL\Pagination\Connection;

class MemberTypeCreator extends TypeCreator
{
    public function attributes()
    {
        return [
            'name' => 'member'
        ];
    }

    public function fields()
    {
        return [
            'ID' => ['type' => Type::nonNull(Type::id())],
            'Email' => ['type' => Type::string()],
            'FirstName' => ['type' => Type::string()],
            'Surname' => ['type' => Type::string()],
        ];
    }
}

```

Each type class needs to be registered with a unique name against the schema
through YAML configuration:

```yml
SilverStripe\GraphQL:
  schema:
    types:
      member: 'MyProject\GraphQL\MemberTypeCreator'
```


## Define Queries

Types can be exposed via "queries". These queries are in charge of retrieving
data through the SilverStripe ORM. The response itself is handled by the
underlying GraphQL PHP library, which loops through the resulting `DataList`
and accesses fields based on the referred "type" definition.

**Note:** This will return ALL records. See below for a paginated example.

```php
<?php

namespace MyProject\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\QueryCreator;

class ReadMembersQueryCreator extends QueryCreator implements OperationResolver
{
    public function attributes()
    {
        return [
            'name' => 'readMembers'
        ];
    }

    public function args()
    {
        return [
            'Email' => ['type' => Type::string()]
        ];
    }

    public function type()
    {
        // Return a "thunk" to lazy load types
        return function () {
            return Type::listOf($this->manager->getType('member'));
        };
    }

    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        $member = Member::singleton();
        if (!$member->canView($context['currentUser'])) {
            throw new \InvalidArgumentException(sprintf(
                '%s view access not permitted',
                Member::class
            ));
        }
        $list = Member::get();

        // Optional filtering by properties
        if (isset($args['Email'])) {
            $list = $list->filter('Email', $args['Email']);
        }

        return $list;
    }
}
```

We'll register the query with a unique name through YAML configuration:

```yml
SilverStripe\GraphQL:
  schema:
    queries:
      readMembers: 'MyProject\GraphQL\ReadMembersQueryCreator'
```

You can query data with the following URL:

```
/graphql/?query={readMembers{ID+FirstName+Email}}
```

The query contained in the `query` parameter can be reformatted as follows:

```graphql
{
  readMembers {
    ID
    FirstName
    Email
  }
}
```

You can apply the `Email` filter in the above example like so:

```graphql
query ($Email: String) {
  readMembers(Email: $Email) {
    ID
    FirstName
    Email
  }
}
```

And add a query variable:

```json
{
  "Email": "john@example.com"
}
```

## Pagination

The GraphQL module also provides a wrapper to return paginated and sorted
records using offset based pagination.

> This module currently does not support Relay (cursor based) pagination.
> [This blog post](https://dev-blog.apollodata.com/understanding-pagination-rest-graphql-and-relay-b10f835549e7#.kg5qkwvuz)
> describes the differences.

To have a `Query` return a page-able list of records queries should extend the
`PaginatedQueryCreator` class and return a `Connection` instance.

```php
<?php

namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Pagination\PaginatedQueryCreator;

class PaginatedReadMembersQueryCreator extends PaginatedQueryCreator
{
    public function connection()
    {
        return Connection::create('paginatedReadMembers')
            ->setConnectionType(function () {
                return $this->manager->getType('member');
            })
            ->setArgs([
                'Email' => [
                    'type' => Type::string()
                ]
            ])
            ->setSortableFields(['ID', 'FirstName', 'Email'])
            ->setConnectionResolver(function ($obj, $args, $context) {
                $member = Member::singleton();
                if (!$member->canView($context['currentUser'])) {
                    throw new \InvalidArgumentException(sprintf(
                        '%s view access not permitted',
                        Member::class
                    ));
                }
                $list = Member::get();

                // Optional filtering by properties
                if (isset($args['Email'])) {
                    $list = $list->filter('Email', $args['Email']);
                }

                return $list;
            });
    }
}

```

You will need to add a new unique query alias to your configuration:

```yml
SilverStripe\GraphQL:
  schema:
    queries:
      paginatedReadMembers: 'MyProject\GraphQL\PaginatedReadMembersQueryCreator'
```

Using a `Connection` the GraphQL server will return the results wrapped under
the `edges` result type. `Connection` supports the following arguments:

* `limit`
* `offset`
* `sortBy`

Additional arguments can be added by providing the `setArgs` function (such as
`Email` in the previous example). Each argument must be given a specific type.

Pagination information is provided under the `pageInfo` type. This object type
supports the following fields:

* `totalCount` returns the total number of items in the list,
* `hasNextPage` returns whether more records are available.
* `hasPreviousPage` returns whether more records are available by decreasing
the offset.

You can query paginated data with the following URL:

```
/graphql/?query=query+Members{paginatedReadMembers(limit:1,offset:0){edges{node{ID+FirstName+Email}}pageInfo{hasNextPage+hasPreviousPage+totalCount}}}
```

The query contained in the `query` parameter can be reformatted as follows:

```graphql
query Members {
  paginatedReadMembers(limit: 1, offset: 0) {
    edges {
      node {
        ID
        FirstName
        Email
      }
    }
    pageInfo {
      hasNextPage
      hasPreviousPage
      totalCount
    }
  }
}

```

#### Setting Pagination and Sorting options

To limit the ability for users to perform searching and ordering as they wish,
`Collection` instances can define their own limits and defaults.

* `setSortableFields` an array of allowed sort columns.
* `setDefaultLimit` integer for the default page length (default 100)
* `setMaximumLimit` integer for the maximum `limit` records per page to prevent
excessive load trying to load millions of records (default 100)

```php
return Connection::create('paginatedReadMembers')
    // ...
    ->setDefaultLimit(10)
    ->setMaximumLimit(100); // previous users requesting more than 100 records
```

#### Nested Connections

`Connection` can be used to return related objects such as `has_many` and
`many_many` models.

```php
<?php

namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\TypeCreator;
use SilverStripe\GraphQL\Pagination\Connection;

class MemberTypeCreator extends TypeCreator
{
    public function attributes()
    {
        return [
            'name' => 'member'
        ];
    }

    public function fields()
    {
        $groupsConnection = Connection::create('Groups')
            ->setConnectionType(function() {
                return $this->manager->getType('group');
            })
            ->setDescription('A list of the users groups')
            ->setSortableFields(['ID', 'Title']);

        return [
            'ID' => ['type' => Type::nonNull(Type::id())],
            'Email' => ['type' => Type::string()],
            'FirstName' => ['type' => Type::string()],
            'Surname' => ['type' => Type::string()],
            'Groups' => [
                'type' => $groupsConnection->toType(),
                'args' => $groupsConnection->args(),
                'resolve' => function($obj, $args, $context) use ($groupsConnection) {
                    return $groupsConnection->resolveList(
                        $obj->Groups(),
                        $args,
                        $context
                    );
                }
            ]
        ];
    }
}
```

```graphql
query Members {
  paginatedReadMembers(limit: 10) {
    edges {
      node {
        ID
        FirstName
        Email
        Groups(sortBy: [{field: Title, direction: DESC}]) {
          edges {
            node {
              ID
              Title
              Description
            }
          }
          pageInfo {
            hasNextPage
            hasPreviousPage
            totalCount
          }
        }
      }
    }
    pageInfo {
      hasNextPage
      hasPreviousPage
      totalCount
    }
  }
}
```

### Define Mutations

A "mutation" is a specialised GraphQL query which has side effects on your data,
such as create, update or delete. Each of these operations would be expressed
as its own mutation class. Returning an object from the `resolve()` method
will automatically include it in the response.

```php
<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\MutationCreator;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\Security\Member;

class CreateMemberMutationCreator extends MutationCreator implements OperationResolver
{
    public function attributes()
    {
        return [
            'name' => 'createMember',
            'description' => 'Creates a member without permissions or group assignments'
        ];
    }

    public function type()
    {
        return function() {
            return $this->manager->getType('member');
        };
    }

    public function args()
    {
        return [
            'Email' => ['type' => Type::nonNull(Type::string())],
            'FirstName' => ['type' => Type::string()],
            'LastName' => ['type' => Type::string()],
        ];
    }

    public function resolve($object, array $args, $context, $info)
    {
        if (!singleton(Member::class)->canCreate($context['currentUser'])) {
            throw new \InvalidArgumentException('Member creation not allowed');
        }

        return (new Member($args))->write();
    }
}

```

We'll register this mutation through YAML configuration:

```yml
SilverStripe\GraphQL:
  schema:
    mutations:
      createMember: 'MyProject\GraphQL\CreateMemberMutationCreator'
```

You can run a mutation with the following query:

```graphql
mutation ($Email: String!) {
  createMember(Email: $Email) {
    ID
  }
}
```

This will create a new member with an email address, which you can pass in as
query variables: `{"Email": "test@test.com"}`. It'll return the new `ID`
property of the created member.

## Scaffolding DataObjects into the Schema

Making a DataObject accessible through the GraphQL API involves quite a bit of boilerplate. In the above example, we can 
see that creating endpoints for a query and a mutation requires creating three new classes, along with an update to the 
configuration, and we haven't even dealt with data relations yet. For applications that require a lot of business logic 
and specific functionality, an architecture like this affords the developer a lot of control, but for developers who 
just want to make a given model accessible through GraphQL with some basic create, read, update, and delete operations, 
scaffolding them can save a lot of time and reduce the clutter in your project.

Scaffolding DataObjects can be achieved in two non-exclusive ways:

* Via executable code (procedurally)
* Via the config layer (declaratively)

The example code will show demonstrate both methods for each section.

### Our example

For these examples, we'll imagine we have the following model:

```php
namespace MyProject;

class Post extends DataObject {

  private static $db = [
  	'Title' => 'Varchar',
  	'Content' => 'HTMLText'
  ];

  private static $has_one = [
  	'Author' => 'SilverStripe\Security\Member'
  ];

  private static $has_many = [
  	'Comments' => 'MyProject\Comment'
  ];

  private static $many_many = [
  	'Files' => 'SilverStripe\Assets\File'
  ];
}
```

### Scaffolding DataObjects through the Config layer

Many of the declarations you make through procedural code can be done via YAML. If you don't have any logic in your 
scaffolding, using YAML is a simple approach to adding scaffolding.

We'll need to define a `scaffolding` node in the `SilverStripe\GraphQL.schema` setting.

```yaml
SilverStripe\GraphQL:
  schema:
    scaffolding:      
      ## scaffolding will go here

```

### Scaffolding DataObjects through procedural code

Alternatively, for more complex requirements, you can create the scaffolding with code. The GraphQL `Manager` class will 
bootstrap itself with any scaffolders that are registered in its config. These scaffolders must implement the 
`ScaffoldingProvider` interface. A logical place to add this code may be in your DataObject, but it could be anywhere.

As a `ScaffoldingProvider`, the class must now offer the `provideGraphQLScaffolding()` method.

```php
namespace MyProject;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;

class Post extends DataObject implements ScaffoldingProvider {
	//...
    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
    	// update the scaffolder here
	}
}
```

In order to register the scaffolding provider with the manager, we'll need to make an update to the config.

```yaml
SilverStripe\GraphQL:
  schema:
    scaffolding_providers:
      - MyProject\Post
```


### Exposing a DataObject to GraphQL

Let's now expose the `Post` type. We'll choose the fields we want to offer, along with a simple query and mutation. To
resolve queries and mutations, we'll need to specify the name of a resolver class. This class
must implement the `SilverStripe\GraphQL\Scaffolding\ResolverInterface`. (More on this below).

**Via YAML**:
```
SilverStripe\GraphQL:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: [ID, Title, Content]
          operations:
            read: true
            create: true
```

We can now access the posts via GraphQL.
```
query {
	readPosts {
		Title
    	Content
  }
}
```

```
mutation UpdatePost($ID: ID!, $Input: PostUpdateInputType!)
	updatePost(ID:$ID, Input: { Title: "Some new Title" }) {
		Title
	}
}
```

**...Or with code**:
```php
namespace MyProject;

class Post extends DataObject implements ScaffoldingProvider {
	//...
    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
    	$scaffolder
    		->type(Post::class)
	    		->addFields(['ID','Title','Content'])
	    		->operation(SchemaScaffolder::READ)
	    			->end()
	    		->operation(SchemaScaffolder::UPDATE)
	    			->end()
	    		->end();

    	return $scaffolder;
	}
}
```

#### Adding arguments

You can add arguments to basic crud operations, but keep in mind you'll need to use your own
resolver, as the default resolvers will not be aware of any custom arguments you've allowed.

Using YAML, simply use a map of options instead of `true`.

**Via YAML**
```yaml
SilverStripe\GraphQL:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: [ID, Title, Content]
          operations:
            read:
              args:
                StartingWith: String
              resolver: MyProject\ReadPostResolver
            create: true
```

**... Or with code**
```php
	$scaffolder
		->type(Post::class)
    		->addFields(['ID','Title','Content'])
    		->operation(SchemaScaffolder::READ)
    			->addArgs([
    				'StartingWith' => 'String'
    			])
        		->setResolver(function($obj, $args) {
        			$list = Post::get();
        			if(isset($args['StartingWith'])) {
        				$list = $list->filter('Title:StartsWith', $args['StartingWith']);
        			}

        			return $list;
        		})         	
    			->end()
    		->operation(SchemaScaffolder::UPDATE)
    			->end()
    		->end();
```

**GraphQL**
```
query {
  readPosts(StartingWith: "o") {
    edges {
      node {
        Title
        Content
      }
    }
  }
}
```

> Argument definitions are expressed using a shorthand. Append `!` to the argument type to
make it required, and `=SomeValue` to set it to a default value, e.g. `'Category' => 'String!'` or
`'Answer' => 'Int=42'`.

#### Using a custom resolver

As seen in the code example above, the simplest way to add a resolver is via an anonymous function
via the `setResolver()` method. In YAML, you can't define such functions, so resolvers be names or instances of classes thatt implement the 
`ResolverInterface`.

**When using the YAML approach, custom resolver classes are compulsory**, since you can't define closures in YAML.


```php
namespace MyProject\GraphQL;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;

class MyResolver implements ResolverInterface
{
    public function resolve($object, $args, $context, $info)
    {
		$post = Post::get()->byID($args['ID']);
		$post->Title = $args['NewTitle'];
		$post->write();    	
	}
}
```

This resolver class may now be assigned as either an instance, or a string to the query or mutation definition.

```php
	$scaffolder
		->type(Post::class)
			->operation(SchemaScaffolder::UPDATE)
				->setResolver(MyResolver::class)
				->end();
```
Or...
```php
	$scaffolder
		->type(Post::class)
			->operation(SchemaScaffolder::UPDATE)
				->setResolver(new MyResolver())
				->end();
```
#### Configuring pagination and sorting

By default, all queries are paginated and have no sortable fields. Both of these settings are
configurable.

**Via YAML**
```yaml
SilverStripe\GraphQL:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: [ID, Title, Content]
          operations:
            read:
              args:
                StartingWith: String
              resolver: MyProject\ReadPostResolver
              sortableFields: [Title]            
            create: true
        MyProject\Comment:
          fields: [Comment, Author]
          operations:
            read:
              paginate: false
```

**... Or with code**
```php
	$scaffolder
		->type(Post::class)
    		->addFields(['ID','Title','Content'])
    		->operation(SchemaScaffolder::READ)
    			->addArgs([
    				'StartingWith' => 'String'
    			])
        		->setResolver(function($obj, $args) {
        			$list = Post::get();
        			if(isset($args['StartingWith'])) {
        				$list = $list->filter('Title:StartsWith', $args['StartingWith']);
        			}

        			return $list;
        		})
        		->addSortableFields(['Title'])         	
    			->end()
    		->operation(SchemaScaffolder::UPDATE)
    			->end()
    		->end()
    	->type(Comment::class)
    		->addFields(['Comment','Author'])
    		->operation(SchemaScaffolder::READ)
    			->setUsePagination(false)
    			->end();
```

**GraphQL**
```
query readPosts(StartingWith: "a", sortBy: [{field:Title, direction:DESC}]) {
	edges {
		node {
			Title
		}
	}
}
```

```
query readComments {
	Author
	Comment
}
```

#### Adding related objects

The `Post` type we're using has a `$has_one` relation to `Author` (Member), and plural relationships
to `File` and `Comment`. Let's expose both of those to the query.

For the `$has_one`, the relationship can simply be declared as a field. For `$has_many`, `$many_many`,
and any custom getter that returns a `DataList`, we can set up a nested query.


**Via YAML**:
```
SilverStripe\GraphQL:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: [ID, Title, Content, Author]
          operations:
            read:
              args:
                StartingWith: String
              resolver: MyProject\ReadPostResolver
              sortableFields: [Title]            
            create: true
          nestedQueries:
            Comments: true
            Files: true
        MyProject\Comment:
          fields: [Comment, Author]
          operations:
            read:
              paginate: false

```

**... Or with code**:
```php
	$scaffolder
		->type(Post::class)
    		->addFields(['ID','Title','Content', 'Author'])
    		->operation(SchemaScaffolder::READ)
    			->addArgs([
    				'StartingWith' => 'String'
    			])
        		->setResolver(function($obj, $args) {
        			$list = Post::get();
        			if(isset($args['StartingWith'])) {
        				$list = $list->filter('Title:StartsWith', $args['StartingWith']);
        			}

        			return $list;
        		})
        		->addSortableFields(['Title'])         	
    			->end()    			
    		->operation(SchemaScaffolder::UPDATE)
    			->end()
    		->nestedQuery('Comments')
    			->end()
    		->nestedQuery('Files')
    			->end()
    		->end()
    	->type(Comment::class)
    		->addFields(['Comment','Author'])
    		->operation(SchemaScaffolder::READ)
    			->setUsePagination(false)
    			->end();

```

**GraphQL**

```
query {
  readPosts(StartingWith: "a") {
    edges {
      node {
        Title
        Content
        Date
        Author {
        	ID
        }
        Comments {
          edges {
            node {
              Comment
            }
          }
        }
        Files(limit: 2) {
          edges {
            node {
            	ID
            }
          }
        }
      }
    }
  }
}
```

Notice that we can only query the `ID` field of `Files` and `Author`, our new related fields.
This is because the types are implicitly created by the configuration, but only to the point that
they exist in the schema. They won't eagerly add fields. That's still up to you. By default, you'll only
get the `ID` field, as configured in `SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder.default_fields`.

Let's add some more fields.

**Via YAML*:
```yaml
SilverStripe\GraphQL:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          ## ...
        MyProject\Comment:
          ## ...
        SilverStripe\Security\Member
          fields: [FirstName, Surname, Name, Email]
        SilverStripe\Assets\File:
          fields: [Filename, URL]
```

**... Or with code**
```php
	$scaffolder
		->type(Post::class)
		//...
		->type(Member::class)
			->addFields(['FirstName','Surname','Name','Email'])
			->end()
		->type(File::class)
			->addFields(['Filename','URL'])
			->end();
```

Notice that we can freely use the custom getter `Name` on the `Member` record. Fields and `$db` are not one-to-one.

Nested queries can be configured just like operations.

**Via YAML*:
```yaml
SilverStripe\GraphQL:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          ## ...
          nestedQueries:
            Comments:
              args:
                OnlyToday: Boolean
                resolver: MyProject\CommentResolver
          ##...
        ##...
```

**... Or with code**
```php
	$scaffolder
		->type(Post::class)
			->nestedQuery('Comments')
				->addArgs([
					'OnlyToday' => 'Boolean'
				])
            	->setResolver(function($obj, $args, $context) {
            		$comments = $obj->Comments();
            		if(isset($args['OnlyToday']) && $args['OnlyToday']) {
            			$comments = $comments->where('DATE(Created) = DATE(NOW())');
            		}

            		return $comments;
            	})
			->end()
			//...
		//...
```


**GraphQL**

```
query {
  readPosts(StartingWith: "a") {
    edges {
      node {
        Title
        Content
        Date
        Author {
        	Name
        	Email
        }
        Comments(OnlyToday: true) {
          edges {
            node {
              Comment
            }
          }
        }
        Files(limit: 2) {
          edges {
            node {
            	Filename
            	URL
            }
          }
        }
      }
    }
  }
}
```


#### Whitelisting fields in bulk

If you have a type you want to be fairly well exposed, it can be tedious to add each
field piecemeal. As a shortcut, you can use `addAllFieldsExcept()` (code) or `fieldsExcept` (yaml).

**Via YAML**:
```yaml
SilverStripe\GraphQL:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fieldsExcept: [SecretThing]
```

**... Or with code**:
```php
	$scaffolder
		->type(Post::class)
			->addAllFieldsExcept(['SecretThing'])
```

#### Adding arbitrary queries and mutations

Not every operation maps to simple CRUD. For this, you can define custom queries and mutations
in your schema, so long as they map to an existing type.

**Via YAML**
```yaml
SilverStripe\GraphQL:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          ##...
      mutations:
        updatePostTitle:
          type: MyProject\Post
          args:
            ID: ID!
            NewTitle: String!
          resolver: MyProject\UpdatePostResolver
      queries:
        latestPost:
          type: MyProject\Post
          paginate: false
          resolver: MyProject\LatestPostResolver
```

**... Or with code**:
```php
	$scaffolder
		->type(Post::class)
			//...            
        ->mutation('updatePostTitle', Post::class)
            ->addArgs([
                'ID' => 'ID!',
                'NewTitle' => 'String!'
            ])
            ->setResolver(function($obj, $args) {
                $post = Post::get()->byID($args['ID']);
                if($post->canEdit()) {
                    $post->Title = $args['NewTitle'];
                    $post->write();
                }

                return $post;
            })
            ->end()
	    ->query('latestPost', Post::class)
        	->setUsePagination(false)
        	->setResolver(function($obj, $args) {
        		return Post::get()->sort('Date', 'DESC')->first();
        	})
        	->end()
```

**GraphQL**
```
mutation updatePostTitle(ID: 123, NewTitle: 'Foo') {
	Title
}
```

```
query latestPost {
	Title
}
```

#### Dealing with inheritence

Adding any given type will implicitly add all of its ancestors, all the way back to `DataObject`.
Any fields you've listed on a descendant that are available on those ancestors will be exposed on the ancestors
as well. For CRUD operations, each ancestor gets its own set of operations and input types.

**Via YAML**:
```yaml
SilverStripe\GraphQL:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          ##...
        SilverStripe\CMS\Model\RedirectorPage:
          fields: [ExternalURL, Content]
          operations:
            read: true
            create: true
        Page:
          fields: [MyCustomField]
```

**... Or with code**:
```php
	$scaffolder
        ->type('SilverStripe\CMS\Model\RedirectorPage')
        	->addFields(['ExternalURL','Content'])
        	->operation(SchemaScaffolder::READ)
        		->end()
        	->operation(SchemaScaffolder::CREATE)
        		->end()
        	->end()
        ->type('Page')
        	->addFields(['MyCustomField'])
        	->end();
```

We now have the following added to our schema:

```
type RedirectorPage {
	ID
	ExternalURL
	Content
	MyCustomField
}

type Page {
	ID
	Content
	MyCustomField
}

type SiteTree {
	ID
	Content
}

query readRedirectorPages {
	RedirectorPage
}

query readPages {
	Page
}

query readSiteTrees {
	SiteTree
}

mutation createRedirectorPage {
	RedirectorPageCreateInputType
}

mutation createPage {
	PageCreateInputType
}

mutation createSiteTree {
	SiteTreeCreateInputType
}
```


### Define Interfaces

TODO

### Define Input Types

TODO

## Testing/Debugging Queries and Mutations

An in-browser IDE for the GraphQL server is available via the [silverstripe-graphql-devtools](https://github.com/silverstripe/silverstripe-graphql-devtools) module.

As an alternative, a [desktop version](https://github.com/skevy/graphiql-app) of this application is also available. (OSX only)

## Authentication

Some SilverStripe resources have permission requirements to perform CRUD operations
on, for example the `Member` object in the previous examples.

If you are logged into the CMS and performing a request from the same session then
the same Member session is used to authenticate GraphQL requests, however if you
are performing requests from an anonymous/external application you may need to
authenticate before you can complete a request.

Please note that when implementing GraphQL resources it is the developer's
responsibility to ensure that permission checks are implemented wherever
resources are accessed.

### Basic Authentication

Silverstripe has built in support for [HTTP basic authentication](https://en.wikipedia.org/wiki/Basic_access_authentication).
It can be configured for GraphQL implementation with YAML configuration (see below).
This is kept separate from the SilverStripe CMS authenticator because GraphQL needs
to use the successfully authenticated member for CMS permission filtering, whereas
the global `BasicAuth` does not log the member in or use it for model security.

#### YAML configuration

You will need to define the class under `SilverStripe\GraphQL.authenticators`.
You can optionally provide a `priority` number if you want to control which
Authenticator is used when multiple are defined (higher priority returns first).

Here's an example for implementing HTTP basic authentication:

```yaml
SilverStripe\GraphQL:
  authenticators:
    - class: SilverStripe\GraphQL\Auth\BasicAuthAuthenticator
      priority: 10
```

#### In GraphiQL

If you want to add basic authentication support to your GraphQL requests you can
do so by adding a custom `Authorization` HTTP header to your GraphiQL requests.

If you are using the [GraphiQL macOS app](https://github.com/skevy/graphiql-app)
this can be done from "Edit HTTP Headers". The `/dev/graphiql` implementation
does not support custom HTTP headers at this point.

Your custom header should follow the following format:

```
# Key: Value
Authorization: Basic aGVsbG86d29ybGQ=
```

`Basic` is followed by a [base64 encoded](https://en.wikipedia.org/wiki/Base64)
combination of your username, colon and password. The above example is `hello:world`.

**Note:** Authentication credentials are transferred in plain text when using HTTP
basic authenticaiton. We strongly recommend using TLS for non-development use.

Example:

```shell
php -r 'echo base64_encode("hello:world");'
# aGVsbG86d29ybGQ=
```

## TODO

 * Permission checks
 * Input/constraint validation on mutations (with third-party validator)
 * CSRF protection (or token-based auth)
 * Create Enum GraphQL types from DBEnum
 * Date casting
 * Schema serialisation/caching (performance)
 * Scaffolding description, deprecation attributes
 * Remove operations/fields via YAML
 * Refine CRUD operations
