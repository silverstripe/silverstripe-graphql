---
title: Upgrading from GraphQL 3
summary: A high-level view of what you'll need to change when upgrading to GraphQL 4
---

# Upgrading from GraphQL 3

The 4.0 release of `silverstripe-graphql` is underwent a massive set of changes that represent almost an
entire rewrite of the module. This was done as part of a years long plan to improve performance. While
there is no specific upgrade path, there are some key things to look out for and general guidelines on how
to adapt your code from the 3.x release to 4.x.

In this section, we'll cover each of these upgrade issues in order of impact.

## GraphQL schemas require a build step

The most critical change you'll experience moving from 3.x to 4.x is one that affects the developer experience.
The key to improving performance in GraphQL requests was eliminating the overhead of generating the schema at
runtime. This didn't scale. As the GraphQL schema grew, API responses became more latent.

To eliminate this overhead, the GraphQL API relies on **generated code** for the schema. You need to run a
task to build it.

To run the task, use:

`$ vendor/bin/sake dev/tasks/build-schema schema=mySchema`

You can also run the task in the browser:

`http://example.com/dev/tasks/build-schema?schema=mySchema`

[info]
Most of the time, the name of your schema is `default`. If you're editing DataObjects that are accessed
with GraphQL in the CMS, you may have to build the `admin` schema as well.
[/info]

This build process is a larger topic with a few more things to be aware of. To learn more, check out the
[building the schema](getting_started/building_the_schema) docuementation.

## The Manager class, the godfather of GraphQL 3, is gone

`silverstripe-graphql` 3.x relied heavily on the `Manager` class. This became a catch-all that handled
registration of types, execution of scaffolding, running queries and middleware, error handling, and more. This
class has been broken up into separate concerns

* `Schema` <- register your stuff here
* `QueryHandlerInterface` <- Handles GraphQL queries. You'll probably never have to touch it.

### Upgrading

**before**
```yaml
SilverStripe\GraphQL\Manager:
  schemas:
    default:
      types: {}
      queries: {}
      mutations: {}
```

**after**
```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types: {}
      queries: {}
      mutations: {}
```

## TypeCreator, QueryCreator, and MutationCreator are gone

A thorough look at how these classes were being used revealed that they were really just functioning
as value objects that basically just created configuration in a static context. That is, they had no
real reason to be instance-based. Most of the time, they can easily be ported to configuration.

### Upgrading

The first thing you have to do is look at any resolvers that are being used in your creator classes. Make
sure they can be transformed to **static methods**. Resolvers can no longer be instance based. They need to
be rendered as static callables in PHP code. It is rare that one of these creator classes would be relying on
`$this` in its resolver, but just double check.

**before**
```php
class GroupTypeCreator extends TypeCreator
{
    public function attributes()
    {
        return [
            'name' => 'group'
        ];
    }

    public function fields()
    {
        return [
            'ID' => ['type' => Type::nonNull(Type::id())],
            'Title' => ['type' => Type::string()],
            'Description' => ['type' => Type::string()]
        ];
    }
}
```

**after**
```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        group:
          fields:
            ID: ID!
            Title: String
            Description: String
```

That's a simple type, and obviously there's a lot more to it than that, but have a look at the
[working with generic types](getting_started/working_with_generic_types) section of the documentation.

## Resolvers must be static callables

You can no longer use instance methods for resolvers. They can't be easily transformed into generated
PHP code in the schema build step. These resolvers should be refactored to use the `static` declaration
and moved into a class.

**before**
```php
class LatestPostResolver implements OperationResolver
{
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        return Post::get()->sort('Date', 'DESC')->first();
    }
}
```

**after**

```php
class MyResolvers extends DefaultResolverProvider
{
    public static function resolveLatestPost($object, array $args, $context, ResolveInfo $info)
    {
        return Post::get()->sort('Date', 'DESC')->first();
    }
}
```

This method relies on [resolver discovery](getting_started/working_with_generic_types/resolver_discovery),
which you can learn more about in the documentation.

Alternatively, you can hardcode the resolver into your config:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      queries:
        latestPost:
          type: Post
          resolver: ['MyResolvers', 'latestPost' ]
```

## Goodbye, scaffolding, hello models

In the 3.x release, a massive footprint of the codebase was dedicated to a DataObject-specific API called
"scaffolding" that was used to generate types, queries, fields, and more from the ORM. In 4.x, that
approach has been moved to concept called **model types**.

A model type is just a type that is backed by a class that express awareness of its schema (like a DataObject!).
At a high-level, it needs to answer questions like:

* Do you have field X?
What type is field Y?
* What are all the fields you offer?
* What operations do you provide?
* Do you require any extra types to be added to the schema?

The 4.x release ships with a model type implementation specifically for DataObjects, which you can use
a lot like the old scaffolding API.

**before**
```yaml
SilverStripe\GraphQL\Manager:
  schemas:
    default:
      scaffolding:
        types:
          SilverStripe\Security\Member:
            fields: '*'
            operations: '*'
          SilverStripe\CMS\Model\SiteTree:
            fields:
              title: true
              content: true
            operations:
              read: true

```

**after**
```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        SilverStripe\Security\Member:
          fields: '*'
          operations: '*'
        SilverStripe\CMS\Model\SiteTree:
          fields:
            title: true
            content: true
          operations:
            read: true
```

## The Connection class has been moved to plugins

In the 3.x release, you could wrap a query in the `Connection` class to add pagination features.
In 4.x, these features are provided via the new [plugin system](extending/plugins).

The good news is that all DataObject queries are paginated by default, and you shouldn't have to worry about
this, but if you are writing a custom query and want it paginated, check out the section on
[adding pagination to a custom query](getting_started/working_with_generic_types/adding_pagination).

Additionally, the sorting features that were provided by `Connection` have been moved to a plugin dedicated
`SS_List` results. Again, this plugin is applied to all DataObjects by default, and will include all of their
sortable fields by default.

## Query filtering has been moved to a plugin

The previous `QueryFilter` API has been vastly simplified in a new plugin. Filtering is provided to all
read queries by default, and should include all filterable fields, including nested relationships.
