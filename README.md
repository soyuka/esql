# PHP Extended SQL

PHP Extended SQL is an alternative to the also-known DQL (Doctrine Query Language). It combines the flexibility of SQL with the powerful Doctrine metadata to give you more control over queries.

```php
<?php
use App\Car;
use App\Model;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;

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
var_dump($stmt->fetch());
```

To map SQL data to objects ESQL uses janephp/automapper and Doctrine metadata:

```php
<?php
use App\Car;
use Soyuka\ESQL\Bridge\Doctrine\ESQLMapper;

/** $autoMapper is janephp/automapper **/
$mapper = new ESQLMapper($autoMapper, $managerRegistry);
var_dump($mapper->map($stmt->fetch(), Car::class));
```

It supports relations see [MapperTest](https://github.com/soyuka/esql/blob/main/tests/Mapper/MapperTest.php).

## API Platform bridge

This package comes with an API Platform bridge that supports filters and pagination. If you register the bundle we will override the default DataProvider. This bridge will use [`janephp/automapper`](https://github.com/janephp/automapper) to map data to your classes.

Note: Persistence is NOT SUPPORTED yet. Just use Doctrine as it behaves just fine for this.
