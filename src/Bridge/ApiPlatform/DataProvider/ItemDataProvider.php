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

use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;

final class ItemDataProvider implements RestrictedDataProviderInterface, DenormalizedIdentifiersAwareItemDataProviderInterface
{
    private ManagerRegistry $managerRegistry;
    private ESQLMapperInterface $mapper;
    private ESQLInterface $esql;
    private ResourceMetadataFactoryInterface $resourceMetadataFactory;

    public function __construct(ManagerRegistry $managerRegistry, ESQLMapperInterface $mapper, ESQLInterface $esql, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->managerRegistry = $managerRegistry;
        $this->mapper = $mapper;
        $this->esql = $esql;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        $metadata = $this->resourceMetadataFactory->create($resourceClass);

        return $metadata->getAttribute('esql') && $this->managerRegistry->getManagerForClass($resourceClass) instanceof EntityManagerInterface;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        $connection = $this->managerRegistry->getConnection();
        $esql = $this->esql->__invoke($resourceClass);

        $query = <<<SQL
        SELECT {$esql->columns(null)} FROM {$esql->table()} WHERE {$esql->identifier()}
SQL;
        $stmt = $connection->prepare($query);
        $stmt->execute($id);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        /** @var object */
        return $this->mapper->map($data, $resourceClass);
    }
}
