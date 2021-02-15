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
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Soyuka\ESQL\ESQLInterface;

final class CollectionDataProvider implements RestrictedDataProviderInterface, CollectionDataProviderInterface, ContextAwareCollectionDataProviderInterface
{
    private ManagerRegistry $managerRegistry;
    private ESQLInterface $esql;
    private DataPaginator $dataPaginator;
    private ResourceMetadataFactoryInterface $resourceMetadataFactory;
    private iterable $collectionExtensions;
    private LoggerInterface $logger;

    public function __construct(ManagerRegistry $managerRegistry, ESQLInterface $esql, DataPaginator $dataPaginator, ResourceMetadataFactoryInterface $resourceMetadataFactory, iterable $collectionExtensions = [], ?LoggerInterface $logger = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->esql = $esql;
        $this->dataPaginator = $dataPaginator;
        $this->collectionExtensions = $collectionExtensions;
        $this->logger = $logger ?: new NullLogger();
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        $metadata = $this->resourceMetadataFactory->create($resourceClass);

        return $metadata->getAttribute('esql') && $this->managerRegistry->getManagerForClass($resourceClass) instanceof EntityManagerInterface;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $connection = $this->managerRegistry->getConnection();
        $esql = $this->esql->__invoke($resourceClass);

        $query = <<<SQL
        SELECT {$esql->columns()} FROM {$esql->table()}
SQL;

        $parameters = [];
        foreach ($this->collectionExtensions as $extension) {
            if ($extension->supports($resourceClass, $operationName, $context)) {
                [$query, $parameters] = $extension->apply($query, $resourceClass, $operationName, $parameters, $context);
            }
        }

        if ($this->dataPaginator->shouldPaginate($resourceClass, $operationName)) {
            $context[DataPaginator::ESQL] = $esql;

            return $this->dataPaginator->paginate($query, $resourceClass, $operationName, $parameters, $context);
        }

        $stmt = $connection->prepare($query);
        $stmt->execute($parameters);
        $data = $stmt->fetchAll();

        return $esql->map($data);
    }
}
