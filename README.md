# SilverStripe GraphQL Server

[![Build Status](https://travis-ci.org/chillu/silverstripe-graphql.svg?branch=master)](https://travis-ci.org/chillu/silverstripe-graphql)
[![codecov](https://codecov.io/gh/chillu/silverstripe-graphql/branch/master/graph/badge.svg)](https://codecov.io/gh/chillu/silverstripe-graphql)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chillu/silverstripe-graphql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chillu/silverstripe-graphql/?branch=master)

This modules serves SilverStripe data as
[GraphQL](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html) representations,
with helpers to generate schemas based on SilverStripe model introspection.
It uses the [graphql-php](https://github.com/webonyx/graphql-php) library.

## Installation

Require the [composer](http://getcomposer.org) package in your `composer.json`

```
composer require chillu/silverstripe-graphql
```

## Usage

GraphQL is used through a single route which defaults to `/graphql`.
You need to define *Types* and *Queries* to expose your data via this endpoint.

## Configuration

### Define Types


```yml
Chillu\GraphQL:
  schema:
    types:
      member: 'MyProject\GraphQL\MemberTypeCreator'
```

```php
<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use Chillu\GraphQL\TypeCreator;

class MemberTypeCreator extends TypeCreator {

    public function fields() {
        return [
            'ID' => [
                'type' => Type::nonNull(Type::number()),
            ],
            'FirstName' => [
                'type' => Type::string(),
            ],
        ];
    }

}

```

### Define Queries

```yml
Chillu\GraphQL:
  schema:
    queries:
      members: 'MyProject\GraphQL\MemberQueryCreator'
```

```php
<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use Chillu\GraphQL\QueryCreator;
use MyProject\MyDataObject;
use SilverStripe\Security\Member;

class MemberQueryCreator extends QueryCreator {

    public function type()
    {
        return function() {
            return Type::listOf($this->manager->getType('member');
        };
    }


    public function resolve($args)
    {
        $list = Member::get();

        if(isset($args['ID']) {
            $list = $list->filter('ID', $args['ID']);
        }

        return $list;
    }
}

```

You can query data with the following URL:

```
/graphql?query=query+FetchMembers{members{ID,FirstName}}
```

This can also be expressed more cleanly as:

```
{
  query FetchMembers {
    members {
      ID
      FirstName
    }
  }
}
```

## Advanced

### Resolvers

TODO

## TODO

 * InputObject support (less verbose update/create mutations)
 * Input/constraint validation on mutations (with third-party validator)
 * Pagination
 * CSRF protection (or token-based auth)
 * Generate CRUD operations based on DataObject reflection
 * Generate DataObject relationship CRUD operations
 * Create Enum GraphQL types from DBEnum
 * Date casting
 * Interfaces
 * Add types explicitly to generated schema
 * Schema serialisation/caching (performance)
