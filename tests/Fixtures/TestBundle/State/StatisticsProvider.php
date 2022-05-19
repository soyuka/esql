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

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\Persistence\ManagerRegistry;
use Soyuka\ESQL\Bridge\ApiPlatform\State\DataPaginator;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Car;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Model\CarStatistics;
use Symfony\Component\HttpFoundation\RequestStack;

final class StatisticsProvider implements ProviderInterface
{
    private readonly ESQLMapperInterface $mapper;

    public function __construct(private readonly RequestStack $requestStack, private readonly ManagerRegistry $managerRegistry, private readonly ESQLInterface $esql, private readonly DataPaginator $dataPaginator)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $connection = $this->managerRegistry->getConnection();
        $parameters = [];
        $car = $this->esql->__invoke(Car::class, CarStatistics::class, 'car');
        /** @var string */
        $groupBy = $car->columns(['sold', 'color'], $car::AS_STRING | $car::WITHOUT_ALIASES);
        $driver = $connection->getDriver()->getDatabasePlatform();
        $query = match (true) {
            $driver instanceof SQLServerPlatform => <<<SQL
                SELECT AVG(CAST(car.price as BIGINT)) as car_totalPrice, CONCAT(CASE WHEN car.sold = '1' THEN 'sold' ELSE 'not sold' END, COALESCE(car.color, 'No color information')) as car_identifier, {$car->columns(['sold', 'color'])}
                FROM {$car->table()}
                GROUP BY {$groupBy}
                ORDER BY AVG(CAST(car.price as BIGINT)) ASC
SQL,
            default => <<<SQL
                SELECT AVG(car.price) as car_totalPrice, car.sold || COALESCE(car.color, 'No color information') as car_identifier, {$car->columns(['sold', 'color'])}
                FROM {$car->table()}
                GROUP BY {$groupBy}
                ORDER BY AVG(car.price) ASC
SQL,
        };

        if ($paginator = $this->dataPaginator->getPaginator($operation)) {
            return $paginator($car, $query, $parameters, $context);
        }

        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery($parameters);
        $data = $result->fetchAssociative();

        return $car->map($data);
    }
}
