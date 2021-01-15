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

use Doctrine\Persistence\ManagerRegistry;
use Soyuka\ESQL\ESQLInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class SortExtension implements QueryCollectionExtensionInterface
{
    private RequestStack $requestStack;
    private ESQLInterface $esql;
    private ManagerRegistry $managerRegistry;

    public const ORDER_ASC = 'asc';
    public const ORDER_DESC = 'desc';
    public const NULLS_FIRST = 'nullsfirst';
    public const NULLS_LAST = 'nullslast';
    public const PARAMETER_NAME = 'sort';

    public function __construct(RequestStack $requestStack, ESQLInterface $esql, ManagerRegistry $managerRegistry)
    {
        $this->requestStack = $requestStack;
        $this->esql = $esql;
        $this->managerRegistry = $managerRegistry;
    }

    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $parameters = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->query->has(self::PARAMETER_NAME) || null === $sort = $request->query->get(self::PARAMETER_NAME)) {
            return [$query, $parameters];
        }

        ['column' => $getColumn] = $this->esql->__invoke($resourceClass);
        $orderClauses = [];

        foreach (explode(',', $sort) as $sortPredicate) {
            $parts = explode('.', $sortPredicate);
            $property = $parts[0] ?? null;

            // invalid property
            if (!$property || !($column = $getColumn($property))) {
                continue;
            }

            $direction = $this->getPredicate($parts[1] ?? self::ORDER_ASC);
            $nulls = null;
            if ($direction && 0 === strpos($direction, 'nulls')) {
                $nulls = $direction;
                $direction = self::ORDER_ASC;
            }

            $nulls = $nulls ?? $this->getPredicate($parts[2] ?? null);

            foreach ($this->getOrderClause($column, $direction ?? self::ORDER_ASC, $nulls) as $orderClause) {
                $orderClauses[] = $orderClause;
            }
        }

        return [$orderClauses ? $query.' ORDER BY '.implode(', ', $orderClauses) : $query, $parameters];
    }

    private function getPredicate(?string $predicate = null): ?string
    {
        if (null === $predicate) {
            return null;
        }

        switch ($predicate) {
            case self::ORDER_ASC:
            case self::ORDER_DESC:
            case self::NULLS_FIRST:
            case self::NULLS_LAST:
                return $predicate;
            default:
                return self::ORDER_ASC;
        }
    }

    private function getOrderClause(string $column, string $direction, ?string $nulls): iterable
    {
        switch ($this->managerRegistry->getConnection()->getDriver()->getName()) {
            case 'pdo_pgsql':
                yield "{$column} {$direction}".($nulls ? ' NULLS '.(self::NULLS_FIRST === $nulls ? 'FIRST' : 'LAST') : '');
                break;
            case 'pdo_sqlite':
            default:
                if ($nulls) {
                    yield "{$column} ".(self::NULLS_LAST === $nulls ? 'IS NULL' : 'IS NOT NULL');
                }

                yield "{$column} {$direction}";
        }
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return true;
    }
}
