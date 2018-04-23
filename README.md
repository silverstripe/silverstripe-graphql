# SilverStripe GraphQL Server

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-graphql.svg?branch=master)](https://travis-ci.org/silverstripe/silverstripe-graphql)
[![codecov](https://codecov.io/gh/silverstripe/silverstripe-graphql/branch/master/graph/badge.svg)](https://codecov.io/gh/silverstripe/silverstripe-graphql)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/silverstripe/silverstripe-graphql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/silverstripe/silverstripe-graphql/?branch=master)

This modules serves SilverStripe data as
[GraphQL](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html)
representations, with helpers to generate schemas based on SilverStripe model
introspection. It layers a pluggable schema registration system on top of the
[graphql-php](https://github.com/webonyx/graphql-php) library. The APIs are
very similar.

## Installation

Require the [composer](http://getcomposer.org) package in your `composer.json`

```
composer require silverstripe/graphql
```

## Table of contents

 - [Usage](#usage)
 - [Examples](#examples)
 - [Configuration](#configuration)
   - [Define types](#define-types)
   - [Define queries](#define-queries)
   - [Pagination](#pagination)
     - [Setting pagination and sorting options](#setting-pagination-and-sorting-options)
   - [Nested connections](#nested-connections)
   - [Define Mutations](#define-mutations)
 - [Scaffolding DataObjects into the schema](#scaffolding-dataobjects-into-the-schema)
   - [Our example](#our-example)
   - [Scaffolding through the config layer](#scaffolding-through-the-config-layer)
   - [Scaffolding through procedural code](#scaffolding-through-procedural-code)
   - [Exposing a DataObject to GraphQL](#exposing-a-dataobject-to-graphql)
     - [Available operations](#available-operations)
     - [Setting field descriptions](#setting-field-descriptions)
     - [Wildcarding and whitelisting fields](#wildcarding-and-whitelisting-fields)
     - [Adding arguments](#adding-arguments)
     - [Argument definition shorthand](#argument-definition-shorthand)
     - [Adding more definition to arguments](#adding-more-definition-to-arguments)
     - [Using a custom resolver](#using-a-custom-resolver)
     - [Configuring pagination and sorting](#configuring-pagination-and-sorting)
     - [Adding related objects](#adding-related-objects)
     - [Adding arbitrary queries and mutations](#adding-arbitrary-queries-and-mutations)
     - [Dealing with inheritance](#dealing-with-inheritance)
     - [Querying types that have descendants](#querying-types-that-have-descendants)
     - [Customising the names of types and operations](#customising-the-names-of-types-and-operations)
   - [Versioned content](#versioned-content)
     - [Version-specific-operations](#version-specific-operations)
     - [Version-specific arguments](#version-specific-arguments)
     - [Version-specific fields](#version-specific-fields)
   - [Define interfaces](#define-interfaces)
   - [Define input types](#define-input-types)
 - [Extending](#extending)
   - [Adding/removing fields from thirdparty code](#adding-removing-fields-from-thirdparty-code)
   - [Updating the core operations](#updating-the-core-operations)
   - [Adding new operations](#adding-new-operations)
 - [Testing/debugging queries and mutations](#testingdebugging-queries-and-mutations)
 - [Authentication](#authentication)
   - [Default authentication](#default-authentication)
   - [HTTP basic authentication](#http-basic-authentication)
     - [In GraphiQL](#in-graphiql)
   - [Defining your own authenticators](#defining-your-own-authenticators)
 - [Cross-Origin Resource Sharing (CORS)](#cross-origin-resource-sharing-cors)
   - [Sample Custom CORS Config](#sample-custom-cors-config)
 - [TODO](#todo)





## Usage

GraphQL is used through a single route which defaults to `/graphql`. You need
to define *Types* and *Queries* to expose your data via this endpoint.

Currently, the default endpoint (`/graphql`) is protected against access unless the
current user has CMS Access.

## Examples

Code examples can be found in the `examples/` folder (built out from the
configuration docs below).

## Configuration

### Define types

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
SilverStripe\GraphQL\Controller:
  schema:
    types:
      member: 'MyProject\GraphQL\MemberTypeCreator'
```


### Define queries

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
        return Type::listOf($this->manager->getType('member'));
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
SilverStripe\GraphQL\Controller:
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
    edges {
      node {
        ID
        FirstName
        Email
      }
    }
  }
}
```

You can apply the `Email` filter in the above example like so:

```graphql
query ($Email: String) {
  readMembers(Email: $Email) {
    edges {
      node {
        ID
        FirstName
        Email
      }
    }
  }
}
```

And add a query variable:

```json
{
  "Email": "john@example.com"
}
```

You could express this query inline as a single query as below:

```graphql
{
  readMembers(Email: "john@example.com") {
    edges {
      node {
        ID
        FirstName
        Email
      }
    }
  }
}
```

### Pagination

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
    public function createConnection()
    {
        return Connection::create('paginatedReadMembers')
            ->setConnectionType($this->manager->getType('member'))
            ->setArgs([
                'Email' => [
                    'type' => Type::string()
                ]
            ])
            ->setSortableFields(['ID', 'FirstName', 'Email'])
            ->setConnectionResolver(function ($object, array $args, $context, ResolveInfo $info) {
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
SilverStripe\GraphQL\Controller:
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

#### Setting pagination and sorting options

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
    ->setMaximumLimit(100); // prevents users requesting more than 100 records
```

### Nested connections

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
            ->setConnectionType($this->manager->getType('group'))
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
                'resolve' => function($object, array $args, $context, ResolveInfo $info) use ($groupsConnection) {
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

### Define mutations

A "mutation" is a specialised GraphQL query which has side effects on your data,
such as create, update or delete. Each of these operations would be expressed
as its own mutation class. Returning an object from the `resolve()` method
will automatically include it in the response.

```php
<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
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
        return $this->manager->getType('member');
    }

    public function args()
    {
        return [
            'Email' => ['type' => Type::nonNull(Type::string())],
            'FirstName' => ['type' => Type::string()],
            'LastName' => ['type' => Type::string()],
        ];
    }

    public function resolve($object, array $args, $context, ResolveInfo $info)
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
SilverStripe\GraphQL\Controller:
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

## Scaffolding DataObjects into the schema

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

use MyProject\Comment;
use SilverStripe\Assets\File;
use SilverStripe\Security\Member;

class Post extends DataObject
{
    private static $db = [
        'Title' => 'Varchar',
        'Content' => 'HTMLText'
    ];

    private static $has_one = [
        'Author' => Member::class
    ];

    private static $has_many = [
        'Comments' => Comment::class
    ];

    private static $many_many = [
        'Files' => File::class
    ];
}
```

### Scaffolding through the Config layer

Many of the declarations you make through procedural code can be done via YAML. If you don't have any logic in your
scaffolding, using YAML is a simple approach to adding scaffolding.

We'll need to define a `scaffolding` node in the `SilverStripe\GraphQL\Controller.schema` setting.

```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding:
      ## scaffolding will go here

```

### Scaffolding through procedural code

Alternatively, for more complex requirements, you can create the scaffolding with code. The GraphQL `Manager` class will
bootstrap itself with any scaffolders that are registered in its config. These scaffolders must implement the
`ScaffoldingProvider` interface. A logical place to add this code may be in your DataObject, but it could be anywhere.

As a `ScaffoldingProvider`, the class must now offer the `provideGraphQLScaffolding()` method.

```php
namespace MyProject;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\ORM\DataObject;

class Post extends DataObject implements ScaffoldingProvider
{
    //...
    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        // update the scaffolder here
    }
}
```

In order to register the scaffolding provider with the manager, we'll need to make an update to the config.

```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding_providers:
      - MyProject\Post
```

### Exposing a DataObject to GraphQL

Let's now expose the `Post` type. We'll choose the fields we want to offer, along with a simple query and mutation. To
resolve queries and mutations, we'll need to specify the name of a resolver class. This class
must implement the `SilverStripe\GraphQL\OperationResolver`. (More on this below).

**Via YAML**:
```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: [ID, Title, Content]
          operations:
            read: true
            create: true
```


**...Or with code**:

```php
namespace MyProject;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\ORM\DataObject;

class Post extends DataObject implements ScaffoldingProvider
{
    //...
    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        $scaffolder
            ->type(Post::class)
                ->addFields(['ID', 'Title', 'Content'])
                ->operation(SchemaScaffolder::READ)
                    ->end()
                ->operation(SchemaScaffolder::UPDATE)
                    ->end()
                ->end();

        return $scaffolder;
    }
}
```

By declaring these two operations, we have automatically added a new query and
mutation to the GraphQL schema, using naming conventions derived from
the operation type and the `singular_name` or `plural_name` of the DataObject.

```graphql
query {
  readPosts {
    edges {
      node {
        Title
        Content
      }
    }
  }
}
```

```graphql
mutation CreatePost($Input: PostCreateInputType!) {
  createPost(Input: $Input) {
    Title
  }
}
```

```json
{
  "Input": {
    "Title": "My Title"
  }
}
```

Permission constraints (in this case `canView()` and `canCreate()`) are enforced
by the operation resolvers.

#### Available operations
For each type, all the basic `CRUD` operations are afforded to you by default (`create`, `read`, `update`, `delete`),
plus an operation for `readoOne`, which retrieves a record by ID. Each operation can be activated by setting their
identifier to `true` in YAML.

```
...
  operations:
    read: true
    update: true
    create: true
    delete: true
    readOne: true
```

To add configuration to these operations, define a map rather than assigning a boolean.

```
...
  operations:
    read:
      paginate: false
```

Alternatively, when using procedural code, just call `opertation($identifier)`, where `$identifier`
is a constant on the `SchemaScaffolder` class definition.

```php
$scaffolder->type(MyDataObject::class)
  ->operation(SchemaScaffolder::READ)
    ->setUsePagination(false)
  ->end();
```
#### Setting field descriptions

Adding field descriptions is a great way to maintain a well-documented API. To do this,
use a map of `FieldName: 'Your description'` instead of an enumerated list of field names.

**Via YAML**:
```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields:
            ID: The unique identifier of the post
            Title: The title of the post
            Content: The main body of the post (HTML)
          operations:
            read: true
            create: true
```

**...Or with code**:

```php
namespace MyProject;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\ORM\DataObject;

class Post extends DataObject implements ScaffoldingProvider
{
    //...
    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        $scaffolder
            ->type(Post::class)
                ->addFields([
                    'ID' => 'The unique identifier of the post',
                    'Title' => 'The title of the post',
                    'Content' => 'The main body of the post (HTML)'
                ])
                ->operation(SchemaScaffolder::READ)
                    ->end()
                ->operation(SchemaScaffolder::UPDATE)
                    ->end()
                ->end();

            return $scaffolder;
    }
}
```

#### Wildcarding and whitelisting fields

If you have a type you want to be fairly well exposed, it can be tedious to add each
field piecemeal. As a shortcut, you can use `addAllFields()` (code) or `fields: *` (YAML).
If you have specific fields you want omitted from that list, you can use
`addAllFieldsExcept()` (code) or `excludeFields` (YAML).

**Via YAML**:
```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: *
          excludeFields: [SecretThing]
```

**... Or with code**:
```php
$scaffolder
    ->type(Post::class)
        ->addAllFieldsExcept(['SecretThing'])
    ->end()
```


#### Adding arguments

You can add arguments to basic crud operations, but keep in mind you'll need to use your own
resolver, as the default resolvers will not be aware of any custom arguments you've allowed.

Using YAML, simply use a map of options instead of `true`.

**Via YAML**
```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: [ID, Title, Content]
          operations:
            read:
              args:
                Title: String
              resolver: MyProject\ReadPostResolver
            create: true
```

**... Or with code**
```php
$scaffolder
    ->type(Post::class)
        ->addFields(['ID', 'Title', 'Content'])
        ->operation(SchemaScaffolder::READ)
            ->addArgs([
                'Title' => 'String'
            ])
            ->setResolver(function($object, array $args, $context, ResolveInfo $info) {
                if (!singleton(Post::class)->canView($context['currentMember'])) {
                    throw new \Exception('Cannot view Post');
                }
                $list = Post::get();
                if (isset($args['Title'])) {
                    $list = $list->filter('Title:PartialMatch', $args['Title']);
                }

                return $list;
                })
            ->end()
        ->operation(SchemaScaffolder::UPDATE)
            ->end()
        ->end();
```

**GraphQL**
```graphql
query {
  readPosts(Title: "Barcelona") {
    edges {
      node {
        Title
        Content
      }
    }
  }
}
```

#### Argument definition shorthand

You can make your scaffolding delcaration a bit more expressive by using argument shorthand.
* `String!`: A required string
* `Int!(50)`: A required integer with a default value of 50
* `Boolean(0)`: A boolean defaulting to false
* `String`: An optional string, defaulting to null

#### Adding more definition to arguments

To add descriptions, and to use a more granular level of control over your arguments,
you can use a more long-form syntax.

**Via YAML**
```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: [ID, Title, Content]
          operations:
            read:
              args:
                Title: String!
                MinimumCommentCount:
                  type: Int
                  default: 5
                  description: 'Use this parameter to specify the minimum number of comments per post'
              resolver: MyProject\ReadPostResolver
            create: true
```

**... Or with code**
```php
$scaffolder
    ->type(Post::class)
        ->addFields(['ID', 'Title', 'Content'])
        ->operation(SchemaScaffolder::READ)
            ->addArgs([
                'Title' => 'String!',
                'MinimumCommentCount' => 'Int'
            ])
            ->setArgDefaults([
                'MinimumCommentCount' => 5
            ])
            ->setArgDescriptions([
                'MinimumCommentCount' => 'Use this parameter to specify the minimum number of comments per post'
            ])
            ->setResolver(function($object, array $args, $context, ResolveInfo $info) {
                if (!singleton(Post::class)->canView($context['currentMember'])) {
                    throw new \Exception('Cannot view Post');
                }
                $list = Post::get();
                if (isset($args['Title'])) {
                    $list = $list->filter('Title:PartialMatch', $args['Title']);
                }

                return $list;
            })
            ->end()
        ->operation(SchemaScaffolder::UPDATE)
            ->end()
        ->end();
```

#### Using a custom resolver

As seen in the code example above, the simplest way to add a resolver is via an anonymous function
via the `setResolver()` method. In YAML, you can't define such functions, so resolvers be names or instances of
classes that implement the `OperationResolver`.

**When using the YAML approach, custom resolver classes are compulsory**, since you can't define closures in YAML.

```php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\OperationResolver;

class MyResolver implements OperationResolver
{
    public function resolve($object, array $args, $context, ResolveInfo $info)
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
            /* Or...
            ->setResolver(new MyResolver())
            */
            ->end();
```

#### Configuring pagination and sorting

By default, all queries are paginated and have no sortable fields. Both of these settings are
configurable.

**Via YAML**
```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: [ID, Title, Content]
          operations:
            read:
              args:
                Title: String
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
        ->addFields(['ID', 'Title', 'Content'])
        ->operation(SchemaScaffolder::READ)
            ->addArgs([
                'Title' => 'String'
            ])
            ->setResolver(function ($object, array $args, $context, ResolveInfo $info) {
                if (!singleton(Post::class)->canView($context['currentMember'])) {
                    throw new \Exception('Cannot view Post');
                }
                $list = Post::get();
                if (isset($args['Title'])) {
                    $list = $list->filter('Title:PartialMatch', $args['Title']);
                }

                return $list;
            })
            ->addSortableFields(['Title'])
            ->end()
        ->operation(SchemaScaffolder::UPDATE)
            ->end()
        ->end()
    ->type(Comment::class)
        ->addFields(['Comment', 'Author'])
        ->operation(SchemaScaffolder::READ)
            ->setUsePagination(false)
            ->end();
```

**GraphQL**
```graphql
query readPosts(Title: "Japan", sortBy: [{field:Title, direction:DESC}]) {
  edges {
    node {
      Title
    }
  }
}
```

```graphql
query readComments {
  edges {
    node {
      Author
      Comment
    }
  }
}
```

#### Adding related objects

The `Post` type we're using has a `$has_one` relation to `Author` (Member), and plural relationships
to `File` and `Comment`. Let's expose both of those to the query.

For the `$has_one`, the relationship can simply be declared as a field. For `$has_many`, `$many_many`,
and any custom getter that returns a `DataList`, we can set up a nested query.


**Via YAML**:
```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          fields: [ID, Title, Content, Author]
          operations:
            read:
              args:
                Title: String
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
        ->addFields(['ID', 'Title', 'Content', 'Author'])
        ->operation(SchemaScaffolder::READ)
            ->addArgs([
                'Title' => 'String'
            ])
            ->setResolver(function ($object, array $args, $context, ResolveInfo $info) {
                if (!singleton(Post::class)->canView($context['currentMember'])) {
                    throw new \Exception('Cannot view Post');
                }
                $list = Post::get();
                if (isset($args['Title'])) {
                    $list = $list->filter('Title:PartialMatch', $args['Title']);
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
        ->addFields(['Comment', 'Author'])
        ->operation(SchemaScaffolder::READ)
            ->setUsePagination(false)
            ->end();
```

**GraphQL**

```graphql
query {
  readPosts(Title: "Texas") {
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
SilverStripe\GraphQL\Controller:
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
        ->addFields(['FirstName', 'Surname', 'Name', 'Email'])
        ->end()
    ->type(File::class)
        ->addFields(['Filename', 'URL'])
        ->end();
```

Notice that we can freely use the custom getter `Name` on the `Member` record. Fields and `$db` are not one-to-one.

Nested queries can be configured just like operations.

**Via YAML*:
```yaml
SilverStripe\GraphQL\Controller:
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
            ->setResolver(function($object, array $args, $context, ResolveInfo $info) {
                if (!singleton(Comment::class)->canView($context['currentMember'])) {
                    throw new \Exception('Cannot view Comment');
                }
                $comments = $obj->Comments();
                if (isset($args['OnlyToday']) && $args['OnlyToday']) {
                    $comments = $comments->where('DATE(Created) = DATE(NOW())');
                }

                return $comments;
            })
        ->end()
        //...
    //...
```


**GraphQL**

```graphql
query {
  readPosts(Title: "Sydney") {
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

#### Adding arbitrary queries and mutations

Not every operation maps to simple CRUD. For this, you can define custom queries and mutations
in your schema, so long as they map to an existing type.

**Via YAML**
```yaml
SilverStripe\GraphQL\Controller:
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
        ->setResolver(function ($object, array $args, $context, ResolveInfo $info) {
            $post = Post::get()->byID($args['ID']);
            if ($post->canEdit($context['currentMember'])) {
                $post->Title = $args['NewTitle'];
                $post->write();
            }

            return $post;
        })
        ->end()
    ->query('latestPost', Post::class)
        ->setUsePagination(false)
        ->setResolver(function ($object, array $args, $context, ResolveInfo $info) {
            if (singleton(Post::class)->canView($context['currentMember'])) {
                return Post::get()->sort('Date', 'DESC')->first();
            }
        })
        ->end()
```

**GraphQL**
```graphql
mutation updatePostTitle($ID: 123, $NewTitle: 'Foo') {
  Title
}
```

```graphql
query latestPost {
  Title
}
```

Alternatively, if you want to customise a nested query, you can specify `QueryScaffolder` subclass, which
will allow you to write your own resolver and build your own set of arguments. This is particularly
useful if your nested query does not return a `DataList`, from which a dataobject class can be
inferred.

```php
class MyCustomListQueryScaffolder extends ListQueryScaffolder
{
    public function resolve ($obj, array $args, $context, ResolveInfo $info)
    {
        // .. custom query code
    }

    protected function createArgs(Manager $manager)
    {
        return [
            'SpecialArg' => [
                'type' => $manager->getType('SpecialInputType')
            ]
        ];
    }
}
```
**Via YAML**:
```yaml
...
  nestedQueries:
    MyCustomList: My\Project\Scaffolders\MyCustomListQueryScaffolder
```


**... Or with code**:
```php
 $scaffolder->type(MyObject::class)
    ->nestedQuery(
        'MyNestedField',  // the name of the field on the parent object
        new MyCustomListQueryScaffolder(
          'customOperation', // The name of the operation. Must be unique.
          'MyCustomType' // The type the query will return. Make sure it's been registered.
        )
    );
```
#### Dealing with inheritance

Adding any given type will implicitly add all of its ancestors, all the way back to `DataObject`.
Any fields you've listed on a descendant that are available on those ancestors will be exposed on the ancestors
as well. For CRUD operations, each ancestor gets its own set of operations and input types.

When reading types that have exposed descendants (e.g. reading Page, when RedirectorPage is also exposed),
the return type is a *union* of the base type and all exposed descendants. This union type takes on the name
`{BaseType}WithDescendants`.

**Via YAML**:
```yaml
SilverStripe\GraphQL\Controller:
  schema:
    scaffolding:
      types:
        MyProject\Post:
          ##...
        SilverStripe\CMS\Model\RedirectorPage:
          fields: [ID, ExternalURL, Content]
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
        ->addFields(['ID', 'ExternalURL', 'Content'])
        ->operation(SchemaScaffolder::READ)
            ->setName('readRedirectors')
            ->end()
        ->operation(SchemaScaffolder::CREATE)
            ->setName('createRedirector')
            ->end()
        ->end()
    ->type('Page')
        ->addFields(['MyCustomField'])
        ->end();
```

We now have the following added to our schema:

```graphql
type RedirectorPage {
  ID: Int
  ExternalURL: String
  Content: String
  MyCustomField: String
}

type Page {
  ID: Int
  Content: String
  MyCustomField: String
}

type SiteTree {
  ID: Int
  Content: String
}

type PageWithDescendants {
  Page | RedirectorPage
}

type SiteTreeWithDescendants {
  SiteTree | Page | RedirectorPage
}

input RedirectorPageCreateInputType {
  ExternalURL: String
  RedirectionType: String
  MyCustomField: String
  Content: String
  # all other fields from RedirectorPage, Page and SiteTree
}

input PageCreateInputType {
  MyCustomField: String
  Content: String
  # all other fields from Page and SiteTree
}

input SiteTreeCreateInputType {
  # all fields from SiteTree
}

query readRedirectors {
  RedirectorPage
}

query readPages {
  PageWithDescendants
}

query readSiteTrees {
  SiteTreeWithDescendants
}

mutation createRedirector {
  RedirectorPageCreateInputType
}

mutation createPage {
  PageCreateInputType
}

mutation createSiteTree {
  SiteTreeCreateInputType
}
```

#### Querying types that have descendants

Keep in mind that when querying a base class that has descendant types exposed (e.g. querying `Page`
when `RedirectorPage` is also exposed), a union is returned, and you will need to resolve it
with the `...on {type}` GraphQL syntax.

```graphql
query readSiteTrees {
  readSiteTrees {
    edges {
      node {
        __typename
        ...on Page {
          ID
        }
        ...on RedirectorPage {
          RedirectionType
        }
      }
    }
  }
}
```

#### Customising the names of types and operations

By default, the scaffolder will generate a type name for you based on the dataobject's `$table_name`
setting and the output of its `singular_name()` method. Often times, these are poor proxies for
a canonical name, e.g. `readMy_Really_Long_NameSpaced_BlogPosts`. To customise the type name, simply map a name to it in the `SilverStripe\GraphQL\Scaffolding\Schema`
class.

```yaml
SilverStripe\GraphQL\Controller:
  schema:
    typeNames:
      My\Really\Long\Namespaced\BlogPost: Blog
```

Note that `typeNames` is the mapping of dataobjects to the graphql types, whereas the `types`
config is the list of type creators for non-scaffolded types, backed by php classes.
`typeNames` is also used (and required by) scaffolding, whether via PHP or YML.

Operations names are expressed using the type name of the dataobject they serve. That type name
may be customised or computed automatically, as described above. For a deeper level of control, you can
name the operation using the `name` property.

```yaml
...
  operations:
    read:
      name: currentBlogs
```

The name of the operation has been fully customised to `currentBlogs`, returning the type `Blog`.

```yaml
...
  operations:
    read: true
```

Otherwise, the name of the read operation, given the `Schema` config above, will be `readBlogs`.


### Versioned content

If the `silversrtripe/versioned` module is installed in your project (as it is with a default CMS install),
a series of schema updates specific to versioning will be provided to all types that use the `Versioned` extension.
These include:

#### Version-specific operations
* `publish<MyType>(ID: Int!)`
* `unpublish<MyType>(ID: Int!)`
* `copy<MyType>ToStage(Input: CopyToStageInputType { ID: Int, FromStage: String, ToStage: String, Version: Int })`

```yaml
...
  operations:
    publish: true
    unpublish: true
    copyToStage: true
```

#### Version-specific arguments
Types that use the `Versioned` extension will also have their `read` operations extended to accept
a `Versioning` parameter which allows you define very specifically what versioning filters to apply
to the result set.

**The "Versioning" input**
<table>
<tr>
  <th>Field</th>
  <th>Description</th>
</tr>
<tr>
  <td>Mode</td>
  <td>
    <p>One of:</p>
    <ul>
      <li><strong>ARCHIVE</strong> (Read from a specific date in the archive)</li>
      <li><strong>LIVE</strong> (Read from the live stage)</li>
      <li><strong>DRAFT</strong> (Read from the draft stage)</li>
      <li><strong>LATEST</strong> (Read the latest version from each record)</li>
      <li><strong>STATUS</strong> (Filter records by their status. Must supply a `Status` parameter)</li>
     </ul>
  </td>
</tr>
<tr>
  <td>ArchiveDate</td>
  <td>The date, in <code>YYYY-MM-DD</code> format to use when in <code>ARCHIVE</code> mode.</td>
</tr>
<tr>
  <td>[Status]</td>
  <td>
    <p>A list of statuses that records must match. Options:</p>
    <ul>
      <li><strong>PUBLISHED</strong> (Include published records)</li>
      <li><strong>DRAFT</strong> (Include draft records)</li>
      <li><strong>MODIFIED</strong> (Include records that have draft changes)</li>
      <li><strong>ARCHIVED</strong> (Include records that have been deleted from stage)</li>
    </ul>
   </td>
</tr>
</table>


**GraphQL**
```
readBlogPosts(Versioning: {
  Mode: "DRAFT"
}) {
  Title
}

readBlogPosts(Versioning: {
  Mode: "ARCHIVE",
  ArchiveDate: "2016-11-08"
}) {
  Title
}

readBlogPosts(Versioning: {
  Mode: "STATUS",
  Status: [DRAFT, MODIFIED]
}) {
  Title
}
```

`readOne` operations also allow a `Version` parameter, which allows you to read a specific version.

**GraphQL**
```
readBlogPosts(Version: 5, ID: 100) {
  Title
}
```

#### Version-specific fields

Types that use the `Versioned` extension will also benefit from two new fields:
* `Version:Int` The version number of the record
* `Versions:[<MyTypeName>Version]` A paginated list of all the previous versions of this record. The type
returned by this query contains a few additional fields: `Author:Member`, `Publisher:Member`, and `Published:Boolean`.

**GraphQL**
```
readBlogPosts {
  Title
  Version
  Versions(limit: 5) {
    Title
    Author {
      FirstName
    }
    Publisher {
      Email
    }
    Published
  }
}
```

### Define interfaces

TODO

### Define input types

TODO

## Extending

Many of the scaffolding classes use the `Extensible` trait, allowing you to influence the scaffolding process
with custom needs.

### Adding/removing fields from thirdparty code
Suppose you have a module that adds new fields to dataobjects that use your extension. You can write
and extension for `DataObjectScaffolder` to update the scaffolding before it is sent to the `Manager`.

```php
class MyDataObjectScaffolderExtension extends Extension
{

  public function onBeforeAddToManager(Manager $manager)
  {
    if ($this->owner->getDataObjectInstance()->hasExtension(MyExtension::class)) {
      $this->owner->addField('MyField');
    }
  }
}
```

### Updating the core operations
The basic `CRUD` operations that come with the module are all extensible with`updateArgs` and `augmentMutation` (or `updateList` for read operations).

```php
class MyCreateExtension extends Extension
{

  public function updateArgs(&$args, Manager $manager)
  {
    $args['SendEmail'] = ['type' => Type::bool()];
  }


  public function augmentMutation($object, array $args, $context, ResolveInfo $info)
  {
    if ($args['SendEmail']) {
      MyService::inst()->sendEmail();
      $obj->EmailSent = true;
    }
  }
}
````

### Adding new operations
If you have a custom operation, in addition to `read`, `update`, `delete`, etc., that you want available
to some or all types, you can write one and register it with the scaffolder. Let's suppose we have
an ecommerce module that wants to offer an `addToCart` mutation to any dataobject that implements
the `Product` interface.

```php
class AddToCartOperation extends MutationScaffolder
{

   public function __construct($dataObjectClass)
   {
      parent::__construct($this->createOperationName(), $dataObjectClass);
      if (!$this->getDataObjectInstance() instanceof ProductInterace) {
        throw new InvalidArgumentException(
            'addToCart operation is only for implementors of ProductInterface'
        );
      }
      $this->setResolver(function($object, array $args, $context, ResolveInfo $info) {
        $record = DataObject::get_by_id($this->dataObjectClass, $args['ID']);
        if (!$record) {
          throw new Exception('ID not found');
        }

        $record->addToCart();

        return $record;
      });
   }


   protected function createArgs(Manager $manager)
   {
      return [
        'ID' => ['type' => Type::nonNull(Type::id())]
      ];
   }


   protected function createOperationName()
   {
        return 'add' . ucfirst($this->typeName()) . 'ToCart';
   }

}
```

Now, register it as an opt-in operation.

```yaml
SilverStripe\GraphQL\Scaffolding\Scaffolders\OperationScaffolder:
  operations:
    addToCart: My\Project\AddToCartOperation
```

## Testing/debugging queries and mutations

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

### Default authentication

The `MemberAuthenticator` class is configured as the default option for authentication,
and will attempt to use the current CMS `Member` session for authentication context.

### HTTP basic authentication

Silverstripe has built in support for [HTTP basic authentication](https://en.wikipedia.org/wiki/Basic_access_authentication).
There is a `BasicAuthAuthenticator` which is configured for GraphQL by default, but
will only activate when required. It is kept separate from the SilverStripe CMS
authenticator because GraphQL needs to use the successfully authenticated member
for CMS permission filtering, whereas the global `BasicAuth` does not log the
member in or use it for model security.

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
basic authentication. We strongly recommend using TLS for non-development use.

Example:

```shell
php -r 'echo base64_encode("hello:world");'
# aGVsbG86d29ybGQ=
```

### Defining your own authenticators

You will need to define the class under `SilverStripe\GraphQL\Auth\Handlers.authenticators`.
You can optionally provide a `priority` number if you want to control which
Authenticator is used when multiple are defined (higher priority returns first).

Authenticator classes will need to implement the `SilverStripe\GraphQL\Auth\AuthenticatorInterface`
interface, which requires you to define an `authenticate` method to return a Member, or false, and
and `isApplicable` method which tells the `Handler` whether or not this authentication method
is applicable in the current request context (provided as an argument).

Here's an example for implementing HTTP basic authentication (note that basic auth is enabled by default anyway):

```yaml
SilverStripe\GraphQL\Auth\Handler:
  authenticators:
    - class: SilverStripe\GraphQL\Auth\BasicAuthAuthenticator
      priority: 10
```

## Cross-Origin Resource Sharing (CORS)

By default [CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS) is disabled in the GraphQL Server. This can be easily enabled via YAML:

```yaml
SilverStripe\GraphQL\Controller:
  cors:
    Enabled: true
```

Once you have enabled CORS you can then control four new headers in the HTTP Response.

1. **Access-Control-Allow-Origin.**

 This lets you define which domains are allowed to access your GraphQL API. There are
 4 options:

 * **Blank**:
 Deny all domains (except localhost)

 ```yaml
 Allow-Origin:
 ```

 * **'\*'**:
 Allow requests from all domains.

 ```yaml
 Allow-Origin: '*'
 ```

 * **Single Domain**:

 Allow requests from one specific external domain.

 ```yaml
 Allow-Origin: 'my.domain.com'
 ```

 * **Multiple Domains**:

 Allow requests from multiple specified external domains.

 ```yaml
 Allow-Origin:
   - 'my.domain.com'
   - 'your.domain.org'
 ```

2. **Access-Control-Allow-Headers.**

 Access-Control-Allow-Headers is part of a CORS 'pre-flight' request to identify
 what headers a CORS request may include.  By default, the GraphQL server enables the
 `Authorization` and `Content-Type` headers. You can add extra allowed headers that
 your GraphQL may need by adding them here. For example:

 ```yaml
 Allow-Headers: 'Authorization, Content-Type, Content-Language'
 ```

 **Note** If you add extra headers to your GraphQL server, you will need to write a
 custom resolver function to handle the response.

3. **Access-Control-Allow-Methods.**

 This defines the HTTP request methods that the GraphQL server will handle.  By
 default this is set to `GET, PUT, OPTIONS`. Again, if you need to support extra
 methods you will need to write a custom resolver to handle this. For example:

 ```yaml
 Allow-Methods: 'GET, PUT, DELETE, OPTIONS'
 ```

4. **Access-Control-Max-Age.**

 Sets the maximum cache age (in seconds) for the CORS pre-flight response. When
 the client makes a successful OPTIONS request, it will cache the response
 headers for this specified duration. If the time expires or the required
 headers are different for a new CORS request, the client will send a new OPTIONS
 pre-flight request to ensure it still has authorisation to make the request.
 This is set to 86400 seconds (24 hours) by default but can be changed in YAML as
 in this example:

 ```yaml
 Max-Age: 600
 ```

### Sample Custom CORS Config

```yaml
## CORS Config
SilverStripe\GraphQL\Controller:
  cors:
    Enabled: true
    Allow-Origin: 'silverstripe.org'
    Allow-Headers: 'Authorization, Content-Type'
    Allow-Methods:  'GET, POST, OPTIONS'
    Max-Age:  600  # 600 seconds = 10 minutes.
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
