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
use Soyuka\ESQL\ESQLInterface;

final class ItemProvider implements ProviderInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly ESQLInterface $esql)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $connection = $this->managerRegistry->getConnection();
        $esql = $this->esql->__invoke($operation->getClass());

        $query = <<<SQL
        SELECT {$esql->columns()} FROM {$esql->table()} WHERE {$esql->identifier()}
SQL;
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery($uriVariables);
        $data = $result->fetchAssociative();

        if (!$data) {
            return null;
        }

        /** @var object */
        return $esql->map($data);
    }
}
