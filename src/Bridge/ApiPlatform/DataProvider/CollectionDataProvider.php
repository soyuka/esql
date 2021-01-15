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

namespace Soyuka\ESQL\Bridge\ApiPlatform\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;

final class CollectionDataProvider implements RestrictedDataProviderInterface, CollectionDataProviderInterface, ContextAwareCollectionDataProviderInterface
{
    private ManagerRegistry $managerRegistry;
    private ESQLMapperInterface $mapper;
    private array $eSQL;
    private DataPaginator $dataPaginator;
    private iterable $collectionExtensions;
    private LoggerInterface $logger;

    public function __construct(ManagerRegistry $managerRegistry, ESQLMapperInterface $mapper, ESQLInterface $eSQL, DataPaginator $dataPaginator, iterable $collectionExtensions = [], ?LoggerInterface $logger = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->mapper = $mapper;
        $this->eSQL = $eSQL();
        $this->dataPaginator = $dataPaginator;
        $this->collectionExtensions = $collectionExtensions;
        $this->logger = $logger ?: new NullLogger();
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $this->managerRegistry->getManagerForClass($resourceClass) instanceof EntityManagerInterface;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $connection = $this->managerRegistry->getConnection();
        ['table' => $Table, 'columns' => $Columns, 'joinPredicate' => $JoinPredicate] = $this->eSQL;

        $query = <<<SQL
        SELECT {$Columns($resourceClass)} FROM {$Table($resourceClass)}
SQL;

        $parameters = [];
        foreach ($this->collectionExtensions as $extension) {
            if ($extension->supports($resourceClass, $operationName, $context)) {
                [$query, $parameters] = $extension->apply($query, $resourceClass, $operationName, $parameters, $context);
            }
        }

        if ($this->dataPaginator->shouldPaginate($resourceClass, $operationName)) {
            return $this->dataPaginator->paginate($query, $resourceClass, $operationName, $context);
        }

        $stmt = $connection->prepare($query);
        $stmt->execute($parameters);
        $data = $stmt->fetchAll();

        return $this->mapper->map($data, $resourceClass);
    }
}
