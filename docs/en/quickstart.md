# Quickstart: Expose your dataobjects and pages to a GraphQL API

Once you have a GraphQL server set up, you can start filling out your schema. If all you want
is to expose generic read/write operations to your dataobjects, you can do that
with a minimal amount of configuration.

## Configuration

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

By supplying a value of `*` for `fields` and `operations`, we're saying that we want _all_ of the fields
on site tree. This includes the first level of relationships, as well, as defined on `has_one`, `has_many`,
or `many_many`. Fields on relationships will not inherit the `*` fields selector, and will only expose their ID
by default.

The `*` value on `operations` tells the schema to create all available operations for the dataobject, including
`read`, `readOne`, `create`, `update`, and `delete`. If the `silverstripe/versioned` module is installed, you'll
also get the operations that it provides, including `publish`, `unpublish`, `rollback`, and `copyToStage`.

Now that we've changed our schema, we need to build it using the `build-schema` task:

`$ vendor/bin/sake dev/tasks/build-schema schema=default`

Now, we can access our schema on the default graphql endpoint, `/graphql`.

Test it out!

```graphql
query {
  readSiteTrees {
    nodes {
      title
    }
}
```

By default, you'll also get a bag of goodies with your read operation, including filtering and sorting.

```yaml
query {
  readSiteTrees(
    filter: { title: { eq: "Blog" } },
    sort: { created: DESC } }
  ) {
  nodes {
    title
    created
  }
}

```

### Customising

Let's apply some more granular control over your dataobjects through the configuration layer:

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
            title: true
            price: true
          operations:
            delete: true
            read:
              plugins:
                # only allow one field for filtering
                filter:
                  fields:
                    isFeatured: true
        MyProject\Models\ProductCategory:
          fields:
            title: true
            productCount:
              property: 'Products.Count()'
              type: Int
          operations:
            update: true
            create: true
```

For full documentation on how to customise models, see the [DataObjects and model-backed types](models/index.md)
section of the documentation.

