---
title: The resolver discovery pattern
summary: How you can opt out of mapping fields to resolvers by adhering to naming conventions
---

# Working with generic types

[CHILDREN asList]

## The resolver discovery pattern

When you define a query, mutation, or any other field on a type, you can opt out of providing
an explicit resolver and allow the system to discover one for you based on naming convention.

Let's start by registering a resolver class where we can define a bunch of these methods.

You can register as many classes as makes sense - and each resolver class can have multiple
resolver methods.

```yml
# app/_graphql/config.yml
resolvers:
  - App\GraphQL\Resolver\MyResolver
```

What we're registering here is a generic class that should contain one or more static functions that resolve one
or many fields. How those functions will be discovered relies on the *resolver strategy*.

### Resolver strategy

Each schema config accepts a `resolverStrategy` property. This should map to a callable that will return
a method name given a class name, type name, and [`Field`](api:SilverStripe\GraphQL\Schema\Field\Field) instance.

```php
namespace App\GraphQL\Resolver;

use SilverStripe\GraphQL\Schema\Field\Field;

class Strategy
{
    public static function getResolverMethod(string $className, ?string $typeName = null, ?Field $field = null): ?string
    {
        // strategy logic here
    }
}
```

#### The default resolver strategy

By default, all schemas use [`DefaultResolverStrategy::getResolverMethod()`](api:SilverStripe\GraphQL\Schema\Resolver\DefaultResolverStrategy::getResolverMethod())
to discover resolver functions. The logic works like this:

- Does `resolve<TypeName><FieldName>` exist?
  - Yes? Return that method name
  - No? Continue
- Does `resolve<TypeName>` exist?
  - Yes? Return that method name
  - No? Continue
- Does `resolve<FieldName>` exist?
  - Yes? Return that method name
  - No? Continue
- Does `resolve` exist?
  - Yes? Return that method name
  - No? Return null. This resolver cannot be discovered

Let's look at our query again:

```graphql
query {
  readCountries {
    name
  }
}
```

Imagine we have two classes registered under `resolvers` - `ClassA` and `ClassB`

```yml
# app/_graphql/config.yml
resolvers:
  - App\GraphQL\Resolver\ClassA
  - App\GraphQL\Resolver\ClassB
```

The `DefaultResolverStrategy` will check for methods in this order:

- `ClassA::resolveCountryName()`
- `ClassA::resolveCountry()`
- `ClassA::resolveName()`
- `ClassA::resolve()`
- `ClassB::resolveCountryName()`
- `ClassB::resolveCountry()`
- `ClassB::resolveName()`
- `ClassB::resolve()`
- Return `null`.

You can implement whatever strategy you like in your schema. Just register it to `resolverStrategy` in the config.

```yml
# app/_graphql/config.yml
resolverStrategy: [ 'App\GraphQL\Resolver\Strategy', 'getResolverMethod' ]
```

Let's add a resolver method to our resolver provider:

```php
// app/src/GraphQL/Resolver/MyResolver.php
namespace App\GraphQL\Resolver;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\i18n\Data\Locales;

class MyResolver
{
    public static function resolveReadCountries()
    {
        $results = [];
        $countries = Injector::inst()->get(Locales::class)->getCountries();
        foreach ($countries as $code => $name) {
            $results[] = [
                'code' => $code,
                'name' => $name,
            ];
        }

        return $results;
    }
}
```

Now that we're using logic to discover our resolver, we can remove our resolver method declarations from the individual
queries and instead just register the resolver class.

```yml
# app/_graphql/config.yml
resolvers:
  - App\GraphQL\Resolver\MyResolver
```

```yml
# app/_graphql/schema.yml
  queries:
    readCountries: '[Country]'
```

Re-run the schema build, with a flush (because we created a new PHP class), and let's go!

`vendor/bin/sake graphql:build --schema=default flush=1`

### Field resolvers

A less magical approach to resolver discovery is defining a `fieldResolver` property on your
types. This is a generic handler for all fields on a given type and can be a nice middle
ground between the rigor of hard coding everything at a query level, and the opacity of discovery logic.

```yml
# app/_graphql/schema.yml
types:
  Country:
    fields:
      name: String
      code: String
    fieldResolver: [ 'App\GraphQL\Resolver\MyResolver', 'resolveCountryFields' ]
```

In this case the registered resolver method will be used to resolve any number of fields.
You'll need to do explicit checks for the field name in your resolver to make this work.

```php
namespace App\GraphQL\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class MyResolver
{
    // ...

    public static function resolveCountryFields($obj, $args, $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        if ($fieldName === 'image') {
            return $obj->getImage()->getURL();
        }
        // ...
    }
}
```

### Further reading

[CHILDREN]
