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

```json
{
    "require": {
        "silverstripe/graphql": "1.0.x-dev"
    }
}
```

## Usage

GraphQL is used through a single route which defaults to `/graphql`.
You need to define *Types* and *Queries* to expose your data via this endpoint.

## Configuration

### Define Types


```yml
SilverStripe\GraphQL:
  types:
    member: 'MyProject\GraphQL\MemberTypeCreator'
```

```php
<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\TypeCreator;

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
SilverStripe\GraphQL:
  types:
    members: 'MyProject\GraphQL\MemberQueryCreator'
```

```php
<?php
namespace MyProject\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\QueryCreator;
use MyProject\MyDataObject;

class MemberQueryCreator extends QueryCreator {

    public function type()
    {
        return Type::listOf(GraphQL::type('member'));
    }


    public function resolve($args)
    {
        $list = MyDataObject::get();

        if(isset($args['ID']) {
            $list = $list->filter('ID', $args['ID']);
        }

        return $list;
    }
}

```

You can query data with the following URL:

```
/graphql?query=query+members{ID,FirstName}
```

Here's an example of how the output might look like:

```json
{
  "hero": {
    "__typename": "Droid",
    "name": "R2-D2"
  }
}
```
