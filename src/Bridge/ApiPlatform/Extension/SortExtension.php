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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Soyuka\ESQL\ESQLInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class SortExtension implements QueryCollectionExtensionInterface
{
    private ResourceMetadataFactoryInterface $resourceMetadataFactory;
    private RequestStack $requestStack;
    private ESQLInterface $esql;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, RequestStack $requestStack, ESQLInterface $esql)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->requestStack = $requestStack;
        $this->esql = $esql;
    }

    private function getPredicate(?string $predicate = null): ?string
    {
        if (null === $predicate) {
            return null;
        }
        switch ($predicate) {
            case 'asc':
            case 'desc':
            case 'nullsfirst':
            case 'nullslast':
                return $predicate;
            default:
                return 'asc';
        }
    }

    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $parameters = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->query->has('sort') || null === $sort = $request->query->get('sort')) {
            return [$query, $parameters];
        }

        ['column' => $Column] = $this->esql->__invoke($resourceClass);
        $orderClauses = [];

        foreach (explode(',', $sort) as $sortPredicate) {
            $parts = explode('.', $sortPredicate);
            $property = $parts[0] ?? null;

            // invalid property
            if (!$property || !($column = $Column($property))) {
                continue;
            }

            $direction = $this->getPredicate($parts[1] ?? 'asc');
            $nulls = null;
            if ($direction && 0 === strpos($direction, 'nulls')) {
                $nulls = $direction;
                $direction = 'asc';
            }

            $nulls = $nulls ?? $this->getPredicate($parts[2] ?? null);

            if ($nulls) {
                $orderClauses[] = "{$column} ".('nullslast' === $nulls ? 'IS NULL' : 'IS NOT NULL');
            }

            $orderClauses[] = "{$column} {$direction}";
        }

        return [$orderClauses ? $query.' ORDER BY '.implode(', ', $orderClauses) : $query, $parameters];
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return null !== $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order');
    }
}
