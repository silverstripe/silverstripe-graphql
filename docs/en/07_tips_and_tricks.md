---
title: Tips & Tricks
summary: Miscellaneous useful tips for working with your GraphQL schema
---

# Tips & tricks

## Debugging the generated code

By default, the generated PHP code is put into obfuscated classnames and filenames to prevent poisoning the search
tools within IDEs. Without this, you can search for something like "Page" in your IDE and get both a generated GraphQL
type (probably not what you want) and a `SiteTree` subclass (more likely what you want) in the results and have no easy way
of differentiating between the two.

When debugging, however, it's much easier if these classnames are human-readable. To turn on debug mode, add `DEBUG_SCHEMA=1`
to your environment file. The classnames and filenames in the generated code directory will then match their type names.

> [!WARNING]
> Take care not to use `DEBUG_SCHEMA=1` as an inline environment variable to your build command, e.g.
> `DEBUG_SCHEMA=1 vendor/bin/sake graphql:build` because any activity that happens at run time, e.g. querying the schema
> will fail, since the environment variable is no longer set.

In live mode, full obfuscation kicks in and the filenames become unreadable. You can only determine the type they map
to by looking at the generated classes and finding the `// @type:<typename>` inline comment, e.g. `// @type:Page`.

This obfuscation is handled by the [`NameObfuscator`](api:SilverStripe\GraphQL\Schema\Storage\NameObfuscator) interface.

There are various implementations:

- [`NaiveNameObfuscator`](api:SilverStripe\GraphQL\Schema\Storage\NaiveNameObfuscator): Filename/Classname === Type name (debug only)
- [`HybridNameObfuscator`](api:SilverStripe\GraphQL\Schema\Storage\HybridNameObfuscator): Filename/Classname is a mix of the typename and a md5 hash (default).
- [`HashNameObfuscator`](api:SilverStripe\GraphQL\Schema\Storage\HashNameObfuscator): Filename/Classname is a md5 hash of the type name (non-dev only).

## Getting the type name for a model class

Often times, you'll need to know the name of the type given a class name. There's a bit of context to this.

### Getting the type name from within your app

If you need the type name during normal execution of your app, e.g. to display in your UI, you can rely
on the cached typenames, which are persisted alongside your generated schema code.

```php
use SilverStripe\GraphQL\Schema\SchemaBuilder;

SchemaBuilder::singleton()->read('default')->getTypeNameForClass($className);
```

## Persisting queries

A common pattern in GraphQL APIs is to store queries on the server by an identifier. This helps save
on bandwidth, as the client doesn't need to put a fully expressed query in the request body - they can use a
simple identifier. Also, it allows you to whitelist only specific query IDs, and block all other ad-hoc,
potentially malicious queries, which adds an extra layer of security to your API, particularly if it's public.

To implement persisted queries, you need an implementation of the
[`PersistedQueryMappingProvider`](api:SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider) interface.
By default three are provided, which cover most use cases:

- [`FileProvider`](api:SilverStripe\GraphQL\PersistedQuery\FileProvider): Store your queries in a flat JSON file on the local filesystem.
- [`HTTPProvider`](api:SilverStripe\GraphQL\PersistedQuery\HTTPProvider): Store your queries on a remote server and reference a JSON file by URL.
- [`JSONStringProvider`](api:SilverStripe\GraphQL\PersistedQuery\JSONStringProvider): Store your queries as hardcoded JSON

### Configuring query mapping providers

All of these implementations can be configured through `Injector`.

> [!WARNING]
> Note that each schema gets its own set of persisted queries. In these examples, we're using the `default` schema.

#### FileProvider

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
    class: SilverStripe\GraphQL\PersistedQuery\FileProvider
    properties:
     schemaMapping:
       default: '/var/www/project/query-mapping.json'
```

A flat file in the path `/var/www/project/query-mapping.json` should contain something like:

```json
{"someUniqueID":"query{validateToken{Valid Message Code}}"}
```

> [!WARNING]
> The file path must be absolute.

#### HTTPProvider

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
    class: SilverStripe\GraphQL\PersistedQuery\HTTPProvider
    properties:
     schemaMapping:
       default: 'https://www.example.com/myqueries.json'
```

A flat file at the URL `https://www.example.com/myqueries.json` should contain something like:

```json
{"someUniqueID":"query{readMembers{Name+Email}}"}
```

#### JSONStringProvider

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
    class: SilverStripe\GraphQL\PersistedQuery\HTTPProvider
    properties:
     schemaMapping:
       default: '{"myMutation":"mutation{createComment($comment:String!){Comment}}"}'
```

The queries are hardcoded into the configuration.

### Requesting queries by identifier

To access a persisted query, simply pass an `id` parameter in the request in lieu of `query`.

`GET https://www.example.com/graphql?id=someID`

> [!WARNING]
> Note that if you pass `query` along with `id`, an exception will be thrown.

## Query caching (caution: EXPERIMENTAL)

The [`QueryCachingMiddleware`](api:SilverStripe\GraphQL\Middleware\QueryCachingMiddleware) class is
an experimental cache layer that persists the results of a GraphQL
query to limit unnecessary calls to the database. The query cache is automatically expired when any
`DataObject` that it relies on is modified. The entire cache will be discarded on `?flush` requests.

To implement query caching, add the middleware to your `QueryHandler`

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface.default:
    class: SilverStripe\GraphQL\QueryHandler\QueryHandler
    properties:
      Middlewares:
        cache: '%$SilverStripe\GraphQL\Middleware\QueryCachingMiddleware'
```

And you will also need to apply the [QueryRecorderExtension](api:SilverStripe\GraphQL\Extensions\QueryRecorderExtension) extension to all DataObjects:

```yml
SilverStripe\ORM\DataObject:
  extensions:
    - SilverStripe\GraphQL\Extensions\QueryRecorderExtension
```

> [!WARNING]
> This feature is experimental, and has not been thoroughly evaluated for security. Use at your own risk.

## Schema introspection {#schema-introspection}

Some GraphQL clients such as [Apollo Client](https://www.apollographql.com/apollo-client) require some level of introspection
into the schema. The [`SchemaTranscriber`](api:SilverStripe\GraphQL\Schema\Services\SchemaTranscriber)
class will persist this data to a static file in an event
that is fired on completion of the schema build. This file can then be consumed by a client side library
like Apollo.

```json
{
   "data":{
      "__schema":{
         "types":[
            {
               "kind":"OBJECT",
               "name":"Query",
               "possibleTypes":null
            }
            // etc ...
         ]
      }
   }
}
```

By default, the file will be stored in `public/_graphql/`.

If you need these types for your own uses, add a new handler:

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\EventDispatcher\Dispatch\Dispatcher:
    properties:
      handlers:
        graphqlTranscribe:
          on: [ graphqlSchemaBuild.mySchema ]
          handler: '%$SilverStripe\GraphQL\Schema\Services\SchemaTranscribeHandler'
```

This handler will only apply to events fired in the `mySchema` context.
