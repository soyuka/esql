# Contributing guide

## Tests

Some tests are functional tests via the [ApiTestCase](https://api-platform.com/docs/distribution/testing/#testing-the-api).

```
tests/Fixtures/app/console doctrine:schema:create
./vendor/bin/simple-phpunit
```

ESQL_DB environment variable helps to specify what database to test on, possible values are: `postgres`. Defaults to an empty string for `sqlite`.
