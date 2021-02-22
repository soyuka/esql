<?php

/*
 * This file is part of the ESQL project.
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Doctrine\Persistence\ManagerRegistry;
use Soyuka\ESQL\Bridge\ApiPlatform\DataProvider\DataPaginator;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Dto\CarStatistics as DtoCarStatistics;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Car;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\CarStatistics;
use Symfony\Component\HttpFoundation\RequestStack;

final class StatisticsDataProvider implements RestrictedDataProviderInterface, CollectionDataProviderInterface, ContextAwareCollectionDataProviderInterface
{
    private RequestStack $requestStack;
    private ManagerRegistry $managerRegistry;
    private ESQLMapperInterface $mapper;
    private ESQLInterface $esql;
    private DataPaginator $dataPaginator;

    public function __construct(RequestStack $requestStack, ManagerRegistry $managerRegistry, ESQLInterface $esql, DataPaginator $dataPaginator)
    {
        $this->requestStack = $requestStack;
        $this->managerRegistry = $managerRegistry;
        $this->esql = $esql;
        $this->dataPaginator = $dataPaginator;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return CarStatistics::class === $resourceClass && 'statistics' === $operationName;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $connection = $this->managerRegistry->getConnection();
        $parameters = [];
        $car = $this->esql->__invoke(Car::class, DtoCarStatistics::class, 'car');
        /** @var string */
        $groupBy = $car->columns(['sold', 'color'], $car::AS_STRING | $car::WITHOUT_ALIASES);

        switch ($connection->getDriver()->getName()) {
            case 'pdo_sqlsrv':
                $query = <<<SQL
                SELECT AVG(CAST(car.price as BIGINT)) as car_totalPrice, CONCAT(CASE WHEN car.sold = '1' THEN 'sold' ELSE 'not sold' END, COALESCE(car.color, 'No color information')) as car_identifier, {$car->columns(['sold', 'color'])}
                FROM {$car->table()}
                GROUP BY {$groupBy}
                ORDER BY AVG(CAST(car.price as BIGINT)) ASC
SQL;
                break;
            default:
                $query = <<<SQL
                SELECT AVG(car.price) as car_totalPrice, car.sold || COALESCE(car.color, 'No color information') as car_identifier, {$car->columns(['sold', 'color'])}
                FROM {$car->table()}
                GROUP BY {$groupBy}
                ORDER BY AVG(car.price) ASC
SQL;
        }

        if ($paginator = $this->dataPaginator->getPaginator($resourceClass, $operationName)) {
            return $paginator($car, $query, $parameters, $context);
        }

        $stmt = $connection->prepare($query);
        $stmt->execute($parameters);
        $data = $stmt->fetchAll();

        return $car->map($data);
    }
}
