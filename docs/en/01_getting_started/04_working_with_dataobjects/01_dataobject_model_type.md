---
title: The DataObject model type
summary: An overview of how the DataObject model can influence the creation of types, queries, and mutations
---

# The DataObject model type

In Silverstripe CMS projects, our data tends to be contained in dataobjects almost exclusively,
and the silverstripe-graphql schema API is designed to make adding dataobject content fast and simple.

## Using model types

While it is possible to add dataobjects to your schema as generic types under the `types`
section of the configuration, and their associated queries and mutations under `queries` and
`mutations`, this will lead to a lot of boilerplate code and repetition. Unless you have some
really custom needs, a much better approach is to embrace _convention over configuration_
and use the `models` section of the config.

**Model types** are types that rely on external classes to tell them who they are and what
they can and cannot do. The model can define and resolve fields, auto-generate queries
and mutations, and more.

Naturally, this module comes bundled with a model type for subclasses of `DataObject`.

Let's use the `models` config to expose some content.

**app/_config/my-schema.yml**
```
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        SilverStripe\CMS\Model\SiteTree:
          fields: '*'
          operations: '*'
```

The class `SilverStripe\CMS\Model\SiteTree` is a subclass of `DataObject`, so the bundled model
type will kick in here and provide a lot of assistance in building out this part of our API.

Case in point, by supplying a value of `*` for `fields` , we're saying that we want _all_ of the fields
on site tree. This includes the first level of relationships, as well, as defined on `has_one`, `has_many`,
or `many_many`. Fields on relationships will not inherit the `*` fields selector, and will only expose their ID
by default.

The `*` value on `operations` tells the schema to create all available queries and mutations
 for the dataobject, including:

* `read`
* `readOne`
* `create`
* `update`
* `delete`

Now that we've changed our schema, we need to build it using the `build-schema` task:

`$ vendor/bin/sake dev/tasks/build-schema schema=default`

Now, we can access our schema on the default graphql endpoint, `/graphql`.

Test it out!

A query:
```graphql
query {
  readSiteTrees {
    nodes {
      title
    }
}
```

A mutation (make sure you're authenticated first):
```graphql
mutation {
  createSiteTree(input: {
    title: "my page"
  }) {
    title
    id
  }
}
```

## Going further

Let's add some more dataobjects, but this time, we'll only add a subset of fields and operations.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      models:
        SilverStripe\CMS\Model\SiteTree:
          fields: '*'
          operations: '*'
        MyProject\Models\Product:
          fields:
            onSale: true
            title: true
            price: true
          operations:
            delete: true
        MyProject\Models\ProductCategory:
          fields:
            title: true
            featured: true
```

A couple things to note here:

* By assigning a value of `true` to the field, we defer to the model to infer the type for the field. To override that, we can always add a `type` property:

```yaml
onSale:
  type: Boolean
```

* The mapping of our field names to the DataObject property is case-insensitive. It is a
convention in GraphQL APIs to use lowerCamelCase fields, so this is given by default.
