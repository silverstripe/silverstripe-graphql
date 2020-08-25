# Persisting queries

A common pattern in GraphQL APIs is to store queries on the server by an identifier. This helps save
on bandwidth, as the client need not put a fully expressed query in the request body, but rather a
simple identifier. Also, it allows you to whitelist only specific query IDs, and block all other ad-hoc,
potentially malicious queries, which adds an extra layer of security to your API, particularly if it's public.

To implement persisted queries, you need an implementation of the
`SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider` interface. By default, three are provided,
which cover most use cases:

* `FileProvider`: Store your queries in a flat JSON file on the local filesystem.
* `HTTPProvider`: Store your queries on a remote server and reference a JSON file by URL.
* `JSONStringProvider`: Store your queries as hardcoded JSON

## Configuring query mapping providers

All of these implementations can be configured through `Injector`. Note that each schema gets its
own set of persisted queries. In these examples, we're using the `default`schema.

### FileProvider

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
    class: SilverStripe\GraphQL\PersistedQuery\FileProvider:
      properties:
       schemaMapping:
         default: '/var/www/project/query-mapping.json'
```


A flat file in the path `/var/www/project/query-mapping.json` should contain something like:

```json
{"someUniqueID":"query{validateToken{Valid Message Code}}"}
```
### HTTPProvider

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
    class: SilverStripe\GraphQL\PersistedQuery\HTTPProvider:
      properties:
       schemaMapping:
         default: 'http://example.com/myqueries.json'
```

A flat file at the URL `http://example.com/myqueries.json` should contain something like:

```json
{"someUniqueID":"query{readMembers{Name+Email}}"}
```

### JSONStringProvider

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
    class: SilverStripe\GraphQL\PersistedQuery\HTTPProvider:
      properties:
       schemaMapping:
         default: '{"myMutation":"mutation{createComment($comment:String!){Comment}}"}'
```

The queries are hardcoded into the configuration.

## Requesting queries by identifier

To access a persisted query, simply pass an `id` parameter in the request in lieu of `query`.

`GET http://example.com/graphql?id=someID`

Note that if you pass `query` along with `id`, an exception will be thrown.
