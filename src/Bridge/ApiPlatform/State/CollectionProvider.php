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

namespace Soyuka\ESQL\Bridge\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\Exception\RuntimeException;

final class CollectionProvider implements ProviderInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly ESQLInterface $esql, private readonly DataPaginator $dataPaginator, private readonly iterable $collectionExtensions = [], ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $connection = $this->managerRegistry->getConnection();
        $esql = $this->esql->__invoke($operation->getClass() ?? throw new RuntimeException(sprintf('No class found for operation "%s".', $operation->getName() ?? '')));

        $query = <<<SQL
        SELECT {$esql->columns()} FROM {$esql->table()}
SQL;

        $parameters = [];
        foreach ($this->collectionExtensions as $extension) {
            if ($extension->supports($operation->getClass(), $operation->getName(), $context)) {
                [$query, $parameters, $context] = $extension->apply($query, $operation->getClass(), $operation->getName(), $parameters, $context);
            }
        }

        if ($paginator = $this->dataPaginator->getPaginator($operation)) {
            return $paginator($esql, $query, $parameters, $context);
        }

        $stmt = $connection->prepare($query);
        $stmt->execute($parameters);
        $data = $stmt->fetchAll();

        return $esql->map($data);
    }
}
