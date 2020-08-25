---
title: Strict HTTP method checking (NEEDS UPDATING)
summary: Ensure requests are GET or POST
---

# Strict HTTP Method Checking

According to GraphQL best practices, mutations should be done over `POST`, while queries have the option
to use either `GET` or `POST`. By default, this module enforces the `POST` request method for all mutations.

To disable that requirement, you can remove the `HTTPMethodMiddleware` from your `Manager` implementation.

---------- todo: this doesn't work ----------
```yaml
  SilverStripe\GraphQL\Manager:
    properties:
      Middlewares:
        HTTPMethodMiddleware: false
```
