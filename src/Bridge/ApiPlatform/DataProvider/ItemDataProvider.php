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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Jane\AutoMapper\AutoMapperInterface;
use Soyuka\ESQL\ESQLInterface;

final class ItemDataProvider implements RestrictedDataProviderInterface, DenormalizedIdentifiersAwareItemDataProviderInterface
{
    private ManagerRegistry $managerRegistry;
    private AutoMapperInterface $automapper;
    private array $eSQL;

    public function __construct(ManagerRegistry $managerRegistry, AutoMapperInterface $automapper, ESQLInterface $eSQL)
    {
        $this->managerRegistry = $managerRegistry;
        $this->automapper = $automapper;
        $this->eSQL = $eSQL();
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $this->managerRegistry->getManagerForClass($resourceClass) instanceof EntityManagerInterface;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        $connection = $this->managerRegistry->getConnection();
        ['table' => $Table, 'identifierPredicate' => $IdentifierPredicate] = $this->eSQL;

        $query = <<<SQL
        SELECT * FROM {$Table($resourceClass)} WHERE {$IdentifierPredicate($resourceClass)}
SQL;
        $stmt = $connection->prepare($query);
        $stmt->execute($id);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        /** @var object */
        return $this->automapper->map($data, $resourceClass);
    }
}
