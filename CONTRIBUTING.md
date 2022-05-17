# Contributing guide

## Tests

Some tests are functional tests via the [ApiTestCase](https://api-platform.com/docs/distribution/testing/#testing-the-api).

```
tests/Fixtures/app/console doctrine:schema:create
./vendor/bin/simple-phpunit
```

ESQL_DB environment variable helps to specify what database to test on, possible values are: `postgres`. Defaults to an empty string for `sqlite`.

## Live

Start the development server with the [symfony binary](https://symfony.com/download):

```
symfony --dir tests/Fixtures/app/ server:start
```

Note I often use `VAR_DUMPER_FORMAT=cli`

## Postgres

Run a container with postgres (and the postgis extension, why not):

```
docker run -p '5432:5432' --name postgres-esql -e POSTGRES_DB=esql_test -e POSTGRES_PASSWORD=password -e POSTGRES_USER=esql postgis/postgis:12-3.0-alpine
ESQL_DB=postgres tests/Fixtures/app/console cache:clear
ESQL_DB=postgres tests/Fixtures/app/console d:s:c # doctrine:schema:create
ESQL_DB=postgres vendor/bin/phpunit --stop-on-failure
```

## SQL Server

```
docker run -e 'ACCEPT_EULA=Y' -e 'SA_PASSWORD=ApiPlatformRocks2020!' -p 1433:1433 mcr.microsoft.com/mssql/server:2019-latest
ESQL_DB=sqlsrv tests/Fixtures/app/console cache:clear
ESQL_DB=sqlsrv d:s:c # doctrine:schema:create
ESQL_DB=sqlsrv vendor/bin/phpunit --stop-on-failure
```
