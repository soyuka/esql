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

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Persistence\ManagerRegistry;
use Jane\Component\AutoMapper\AutoMapperInterface;
use Soyuka\ESQL\Bridge\Doctrine\ClassInfoTrait;
use Soyuka\ESQL\ESQLInterface;

final class Processor implements ProcessorInterface
{
    use ClassInfoTrait;

    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly AutoMapperInterface $mapper, private readonly ESQLInterface $esql)
    {
    }

    /**
     * @param mixed $data
     */
    private function persist($data, array $uriVariables = [], array $context = []): mixed
    {
        $esql = $this->esql->__invoke($data);
        $connection = $this->managerRegistry->getConnection();
        /** @var array */
        $binding = $this->mapper->map($data, 'array');

        if ($context['previous_data'] ?? null) {
            $query = <<<SQL
            UPDATE {$esql->table()} SET {$esql->predicates()}
            WHERE {$esql->identifier()}
SQL;
        } else {
            $query = <<<SQL
            INSERT INTO {$esql->table()} ({$esql->columns()}) VALUES ({$esql->parameters($binding)});
SQL;
        }

        $connection->beginTransaction();
        $stmt = $connection->prepare($query);
        $stmt->execute($binding);
        $connection->commit();

        $query = <<<SQL
        SELECT * FROM {$esql->table()} WHERE {$esql->identifier()}
SQL;
        $stmt = $connection->prepare($query);
        $stmt->execute(['id' => ($context['previous_data'] ?? null) ? $context['previous_data']->getId() : $connection->lastInsertId()]);
        $data = $stmt->fetch();

        /** @var object */
        return $esql->map($data);
    }

    /**
     * @param mixed $data
     */
    private function remove($data, array $uriVariables = [], array $context = []): void
    {
        $esql = $this->esql->__invoke($data);
        $connection = $this->managerRegistry->getConnection();
        $connection->beginTransaction();
        $query = <<<SQL
        DELETE FROM {$esql->table()} WHERE {$esql->identifier()}
SQL;
        $stmt = $connection->prepare($query);
        $stmt->execute(['id' => $data->getId()]);
        $connection->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof DeleteOperationInterface) {
            $this->remove($data, $uriVariables, $context);

            return;
        }

        return $this->persist($data, $uriVariables, $context);
    }
}
