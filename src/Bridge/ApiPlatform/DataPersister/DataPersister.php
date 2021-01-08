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

namespace Soyuka\ESQL\Bridge\ApiPlatform\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Doctrine\Persistence\ManagerRegistry;
use Jane\AutoMapper\AutoMapperInterface;
use Soyuka\ESQL\ESQLInterface;

final class DataPersister implements DataPersisterInterface, ContextAwareDataPersisterInterface
{
    use ClassInfoTrait;

    private ManagerRegistry $managerRegistry;
    private AutoMapperInterface $automapper;
    private array $eSQL;

    public function __construct(ManagerRegistry $managerRegistry, AutoMapperInterface $automapper, ESQLInterface $eSQL)
    {
        $this->managerRegistry = $managerRegistry;
        $this->automapper = $automapper;
        $this->eSQL = $eSQL();
    }

    /**
     * @param mixed $data
     */
    public function persist($data, array $context = [])
    {
        ['table' => $Table, 'columns' => $Columns, 'parameters' => $Parameters, 'predicates' => $Predicates, 'identifierPredicate' => $IdentifierPredicate] = $this->eSQL;
        $connection = $this->managerRegistry->getConnection();
        $binding = $this->automapper->map($data, 'array');

        if ($context['previous_data'] ?? null) {
            $query = <<<SQL
            UPDATE {$Table($data)} SET {$Predicates($data)}
            WHERE {$IdentifierPredicate($data)}
SQL;
        } else {
            $query = <<<SQL
            INSERT INTO {$Table($data)} ({$Columns($data)}) VALUES ({$Parameters($binding)});
SQL;
        }

        $connection->beginTransaction();
        $stmt = $connection->prepare($query);
        $stmt->execute($binding);
        $connection->commit();

        $query = <<<SQL
        SELECT * FROM {$Table($data)} WHERE {$IdentifierPredicate($data)}
SQL;
        $stmt = $connection->prepare($query);
        $stmt->execute(['id' => ($context['previous_data'] ?? null) ? $context['previous_data']->getId() : $connection->lastInsertId()]);
        $data = $stmt->fetch();

        /** @var object */
        return $this->automapper->map($data, $this->getObjectClass($data));
    }

    /**
     * @param mixed $data
     */
    public function remove($data, array $context = []): void
    {
        ['table' => $Table, 'identifierPredicate' => $IdentifierPredicate] = $this->eSQL;
        $connection = $this->managerRegistry->getConnection();
        $connection->beginTransaction();
        $query = <<<SQL
        DELETE FROM {$Table($data)} WHERE {$IdentifierPredicate($data)}
SQL;
        $stmt = $connection->prepare($query);
        $stmt->execute(['id' => $data->getId()]);
        $connection->commit();
    }

    /**
     * @param mixed $data
     */
    public function supports($data, array $context = []): bool
    {
        return null !== $this->managerRegistry->getManagerForClass($this->getObjectClass($data));
    }
}
