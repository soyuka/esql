# PHP Extended SQL

PHP Extended SQL is an alternative to the also-known DQL (Doctrine Query Language). It combines the flexibility of SQL with the powerful Doctrine metadata to give you more control over queries.

```php
<?php
use App\Car;
use App\Model;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\Bridge\Doctrine\ESQLMapper;

$connection = $managerRegistry->getConnection();
$esql = new ESQL($managerRegistry)
[
  'table' => $table,
  'identifier' => $identifier,
  'columns' => $columns,
  'join' => $join
] = $esql(Car::class);
['table' => $modelTable, 'columns' => $modelColumns] = $esql(Model::class);

$query = <<<SQL
SELECT {$columns()}, {$modelColumns()} FROM {$table} 
INNER JOIN {$modelTable} ON {$join(Model::class)}
WHERE {$identifier()}
SQL;

$stmt = $connection->prepare($query);
$stmt->execute(['id' => 1]);
$data = $stmt->fetch();

// Use the ESQLMapper to transform this array to objects:
$mapper = new ESQLMapper($autoMapper, $managerRegistry);
dump($mapper->map($stmt->fetch(), Car::class));
```

## API Platform bridge

This package comes with an API Platform bridge that supports filters and pagination. To use our bridge, use the `esql` attribute:


```php
<?php

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(attributes={"esql"=true})
 */
 class Car {}
```

# TODO
  - ESQLMapper remove doctrine link and use ESQL
  - ESQLMapper with symfony serializer
  - Register auto filter for documentation
