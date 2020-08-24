
# Schema introspection

Some GraphQL clients such as [Apollo](http://apollographql.com) require some level of introspection
into the schema. While introspection is [part of the GraphQL spec](http://graphql.org/learn/introspection/),
this module provides a limited API for fetching it via non-graphql endpoints. By default, the `graphql/`
controller provides a `types` action that will return the type schema (serialised as JSON) dynamically.

*GET http://example.com/graphql/types*
```js
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

```

As your schema grows, introspecting it dynamically may have a performance hit. Alternatively,
if you have the `silverstripe/assets` module installed (as it is in the default SilverStripe installation),
GraphQL can cache your schema as a flat file in the `assets/` directory. To enable this, simply
set the `cache_types_in_filesystem` setting to `true` on `SilverStripe\GraphQL\Controller`. Once enabled,
a `types.graphql` file will be written to your `assets/` directory on `flush`.

When `cache_types_in_filesystem` is enabled, it is recommended that you remove the extension that
provides the dynamic introspection endpoint.

```php
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\Extensions\IntrospectionProvider;

Controller::remove_extension(IntrospectionProvider::class);
```
