# PHP Extended SQL

PHP Extended SQL is an alternative to the famous DQL (Doctrine Query Language). It combines the flexibility of SQL with the powerful Doctrine metadata to give you more control over queries.

```php
<?php
use App\Car;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;

$connection = $managerRegistry->getConnection();
['table' => $Table, 'identifierPredicate' => $IdentifierPredicate] = new ESQL($managerRegistry)();

$query = <<<SQL
SELECT * FROM {$Table(Car::class)} WHERE {$IdentifierPredicate(Car::class)}
SQL;

$stmt = $connection->prepare($query);
$stmt->execute(['id' => 1]);
var_dump($stmt->fetch());
```

## API Platform bridge

This package comes with an API Platform bridge that supports filters and pagination. If you register the bundle we will override the default DataProvider and DataPersister. This bridge will use [`janephp/automapper`](https://github.com/janephp/automapper) to map data to your classes.
