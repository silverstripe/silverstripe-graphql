# Silverstripe CMS GraphQL Server

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-graphql.svg?branch=master)](https://travis-ci.org/silverstripe/silverstripe-graphql)
[![codecov](https://codecov.io/gh/silverstripe/silverstripe-graphql/branch/master/graph/badge.svg)](https://codecov.io/gh/silverstripe/silverstripe-graphql)
[![SilverStripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

This modules serves SilverStripe data as
[GraphQL](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html)
representations, with helpers to generate schemas based on SilverStripe model
introspection. It layers a pluggable schema registration system on top of the
[graphql-php](https://github.com/webonyx/graphql-php) library. The APIs are
very similar.


## This is the 4.x release branch

If you are looking for version 3 [check the `3` branch](https://github.com/silverstripe/silverstripe-graphql/tree/3), which contains documentation inlined into the README.


## Installing on silverstripe/recipe-cms < 4.11

If your project uses `silverstripe/recipe-cms`, you cannot install the stable version 4 release. You can use version 3 (which will be installed by default), or you can swap to the alpha of version 4 by running this command:

```
composer require silverstripe/graphql:^4.0.0-alpha --with-all-dependencies
```


## Documentation

See [doc.silverstripe.org](https://doc.silverstripe.org/en/4/developer_guides/graphql/).
