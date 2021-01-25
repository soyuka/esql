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
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\Exception\RuntimeException;
use Soyuka\ESQL\Filter\FilterParserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class FilterExtension implements QueryCollectionExtensionInterface
{
    private ResourceMetadataFactoryInterface $resourceMetadataFactory;
    private RequestStack $requestStack;
    private ESQLInterface $esql;
    private FilterParserInterface $filterParser;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, RequestStack $requestStack, ESQLInterface $esql, FilterParserInterface $filterParser)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->requestStack = $requestStack;
        $this->esql = $esql;
        $this->filterParser = $filterParser;
    }

    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $parameters = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return [$query, $parameters];
        }

        $esql = $this->esql->__invoke($resourceClass);
        $filterQuery = '';
        $propFilters = [];
        foreach ($request->query->all() as $key => $value) {
            if ('or' === $key || 'and' === $key || !$esql->column($key)) {
                continue;
            }

            $propFilters[] = "$key.$value";
        }

        if (null !== $and = $request->query->get('and')) {
            $filterQuery = $propFilters ? 'and'.substr($and, 0, -1).','.implode(',', $propFilters).')' : "and$and";
        } elseif ($propFilters) {
            $filterQuery = 'and('.implode(',', $propFilters).')';
        }

        if (null !== $or = $request->query->get('or')) {
            $filterQuery .= '' === $filterQuery ? "or($or)" : ",or$or";
        }

        if (!$filterQuery) {
            return [$query, $parameters];
        }

        [$filterSQL, $filterParameters] = $this->filterParser->parse($filterQuery, $resourceClass);

        $parameters = $filterParameters + $parameters;

        Context::setMode('NO_ENCLOSING_QUOTES');
        $parser = new Parser($query);
        $statement = $parser->statements[0];

        if (!$statement instanceof SelectStatement) {
            throw new RuntimeException('Only select statements are supported');
        }

        if (!$statement->where) {
            $statement->where = [new Condition($filterSQL)];
        }

        return [$statement->build(), $parameters];
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return true;
    }
}
