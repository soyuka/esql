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

namespace Soyuka\ESQL\Bridge\ApiPlatform\Extension;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Persistence\ManagerRegistry;
use Soyuka\ESQL\Bridge\ApiPlatform\State\DataPaginator;
use Soyuka\ESQL\ESQLInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class SortExtension implements QueryCollectionExtensionInterface
{
    public const ORDER_ASC = 'asc';
    public const ORDER_DESC = 'desc';
    public const NULLS_FIRST = 'nullsfirst';
    public const NULLS_LAST = 'nullslast';
    public const PARAMETER_NAME = 'sort';

    public function __construct(private readonly RequestStack $requestStack, private readonly ESQLInterface $esql, private readonly ManagerRegistry $managerRegistry)
    {
    }

    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $parameters = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->query->has(self::PARAMETER_NAME) || null === $sort = $request->query->get(self::PARAMETER_NAME)) {
            return [$query, $parameters, $context];
        }

        $esql = $this->esql->__invoke($resourceClass);
        $orderClauses = [];

        foreach (explode(',', (string) $sort) as $sortPredicate) {
            $parts = explode('.', $sortPredicate);
            $property = $parts[0] ?? null;

            // invalid property
            if (!$property || !($column = $esql->column($property))) {
                continue;
            }

            $direction = $this->getPredicate($parts[1] ?? self::ORDER_ASC);
            $nulls = null;
            if ($direction && str_starts_with($direction, 'nulls')) {
                $nulls = $direction;
                $direction = self::ORDER_ASC;
            }

            $nulls ??= $this->getPredicate($parts[2] ?? null);

            foreach ($this->getOrderClause($column, $direction ?? self::ORDER_ASC, $nulls) as $orderClause) {
                $orderClauses[] = $orderClause;
            }
        }

        $context[DataPaginator::ORDER_BY] = implode(', ', $orderClauses);

        return [$orderClauses ? $query.' ORDER BY '.$context[DataPaginator::ORDER_BY] : $query, $parameters, $context];
    }

    private function getPredicate(?string $predicate = null): ?string
    {
        if (null === $predicate) {
            return null;
        }

        return match ($predicate) {
            self::ORDER_ASC, self::ORDER_DESC, self::NULLS_FIRST, self::NULLS_LAST => $predicate,
            default => self::ORDER_ASC,
        };
    }

    private function getOrderClause(string $column, string $direction, ?string $nulls): iterable
    {
        switch ($this->managerRegistry->getConnection()->getDriver()->getDatabasePlatform()::class) {
            case PostgreSQLPlatform::class:
                yield "{$column} {$direction}".($nulls ? ' NULLS '.(self::NULLS_FIRST === $nulls ? 'FIRST' : 'LAST') : '');
                break;
            case SqlitePlatform::class:
                if ($nulls) {
                    yield "{$column} ".(self::NULLS_LAST === $nulls ? 'IS NULL' : 'IS NOT NULL');
                }

                yield "{$column} {$direction}";
                break;
            default:
                yield "{$column} {$direction}";
        }
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return true;
    }
}
