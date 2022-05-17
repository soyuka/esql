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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\Pagination\PartialPaginatorInterface as ApiPlatformPartialPaginatorInterface;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\Persistence\ManagerRegistry;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Exception\InvalidArgumentException;
use Soyuka\ESQL\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @experimental
 */
class DataPaginator
{
    final public const REGEX_LAST_SELECT = '~SELECT(?![^(]*\))~i';
    final public const ORDER_BY = 'esql_order_by';

    public function __construct(private readonly RequestStack $requestStack, private readonly ManagerRegistry $managerRegistry, private readonly ResourceMetadataFactoryInterface $resourceMetadataFactory, private readonly ESQLMapperInterface $mapper, private readonly PaginationOptions $paginationOptions, private readonly ?int $itemsPerPage = 30, private readonly ?int $maximumItemsPerPage = null, private readonly bool $partialPaginationEnabled = false, private readonly ?bool $clientPartialPagination = null, private readonly string $partialPaginationParameterName = 'partial')
    {
    }

    public function getPaginator(Operation $operation): ?\Closure
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request && ($this->isPaginationEnabled($request, $operation) || $this->isPartialPaginationEnabled(
            $request,
            $operation
        ))) {
            return fn (ESQLInterface $esql, string $query, array $parameters, array $context = []) => $this->paginate($esql, $query, $parameters, $this->getPaginationOptions($operation), $context);
        }

        return null;
    }

    public function getPaginationOptions(Operation $operation): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new RuntimeException('Not in a request');
        }

        $isPartialEnabled = $this->isPartialPaginationEnabled(
            $request,
            $operation
        );

        $itemsPerPage = $operation->getPaginationItemsPerPage() ?? $this->itemsPerPage ?? 30.0;
        if ($operation->getPaginationClientItemsPerPage() ?? $this->paginationOptions->getClientItemsPerPage()) {
            $maxItemsPerPage = $operation->getPaginationMaximumItemsPerPage() ?? $this->maximumItemsPerPage;
            $itemsPerPage = (int) $this->getPaginationParameter($request, $this->paginationOptions->getItemsPerPageParameterName(), $itemsPerPage);
            $itemsPerPage = (null !== $maxItemsPerPage && $itemsPerPage >= $maxItemsPerPage ? $maxItemsPerPage : $itemsPerPage);
        }

        if (0 > $itemsPerPage) {
            throw new InvalidArgumentException('Item per page parameter should not be less than 0');
        }

        $page = (int) $this->getPaginationParameter($request, $this->paginationOptions->getPaginationPageParameterName() ?: 'page', 1);
        if (1 > $page) {
            throw new InvalidArgumentException('Page should not be less than 1');
        }

        if (0 === $itemsPerPage && 1 < $page) {
            throw new InvalidArgumentException('Page should not be greater than 1 if itemsPerPage is equal to 0');
        }

        $firstResult = ($page - 1) * $itemsPerPage;
        $nextResult = $firstResult + $itemsPerPage;

        return ['itemsPerPage' => $itemsPerPage, 'firstResult' => $firstResult, 'nextResult' => $nextResult, 'page' => $page, 'partial' => $isPartialEnabled];
    }

    protected function paginate(ESQLInterface $esql, string $query, array $parameters, array $paginationOptions, array $context = []): ApiPlatformPartialPaginatorInterface
    {
        ['itemsPerPage' => $itemsPerPage, 'firstResult' => $firstResult, 'nextResult' => $nextResult, 'page' => $page, 'partial' => $isPartialEnabled] = $paginationOptions;

        $originalQuery = $query;
        $driverName = $this->managerRegistry->getConnection()->getDriver()->getDatabasePlatform()::class;
        switch ($driverName) {
            case SQLServerPlatform::class:
                Context::setMode('NO_ENCLOSING_QUOTES');
                $parser = new Parser($query);
                /** @var string */
                $orderBy = $context[self::ORDER_BY] ?? $esql->columns(null, ESQLInterface::IDENTIFIERS | ESQLInterface::WITHOUT_ALIASES | ESQLInterface::WITHOUT_JOIN_COLUMNS | ESQLInterface::AS_STRING);

                if (\count($parser->errors) || !isset($parser->statements[0]) || !$parser->statements[0] instanceof SelectStatement) {
                    /** @var string */
                    $query = preg_replace(self::REGEX_LAST_SELECT, "SELECT * FROM (SELECT ROW_NUMBER() OVER(ORDER BY {$orderBy}) AS RowNumber,", $query, 1);
                    $query = <<<SQL
$query
) AS paginated
WHERE RowNumber BETWEEN $firstResult AND $nextResult
SQL;
                } else {
                    $statement = $parser->statements[0];
                    if ($statement->order) {
                        $query = "$query OFFSET $firstResult ROWS FETCH NEXT $itemsPerPage ROWS ONLY";
                    } else {
                        $query = "$query ORDER BY $orderBy OFFSET $firstResult ROWS FETCH NEXT $itemsPerPage ROWS ONLY";
                    }
                }
                break;
            default:
                $query = "$query LIMIT $itemsPerPage OFFSET $firstResult";
        }

        $connection = $this->managerRegistry->getConnection();
        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery($parameters);
        $data = $result->fetchAllAssociative();

        if ($data) {
            $data = $esql->map($data);
        }

        return $isPartialEnabled ? new PartialPaginator($data, $page, $itemsPerPage) : new Paginator($data, $page, $itemsPerPage, $this->count($esql, $originalQuery, $parameters, $context));
    }

    protected function count(ESQLInterface $esql, string $query, array $parameters = [], array $context = []): float
    {
        $connection = $this->managerRegistry->getConnection();
        $driverName = $this->managerRegistry->getConnection()->getDriver()->getDatabasePlatform()::class;

        switch ($driverName) {
            case SQLServerPlatform::class:
            /** @var string */
            $orderBy = $context[self::ORDER_BY] ?? $esql->columns(null, ESQLInterface::IDENTIFIERS | ESQLInterface::WITHOUT_ALIASES | ESQLInterface::WITHOUT_JOIN_COLUMNS | ESQLInterface::AS_STRING);

            if (str_contains($query, 'WITH')) {
                $query = preg_replace(self::REGEX_LAST_SELECT, "SELECT MAX(RowNumber) as _esql_count FROM (SELECT ROW_NUMBER() OVER(ORDER BY {$orderBy}) AS RowNumber,", $query, 1);
                $query = <<<SQL
$query
) AS paginated
SQL;
            } else {
                $query = preg_replace(self::REGEX_LAST_SELECT, 'SELECT COUNT(1) OVER () AS _esql_count,', $query, 1);
            }
                break;
            case PostgreSQLPlatform::class:
            case SqlitePlatform::class:
                $query = preg_replace(self::REGEX_LAST_SELECT, 'SELECT COUNT(1) OVER () AS _esql_count,', $query, 1);
                break;
        }

        $stmt = $connection->prepare($query);
        $result = $stmt->executeQuery($parameters);

        ['_esql_count' => $totalItems] = $result->fetchAssociative();

        return (float) $totalItems;
    }

    protected function isPartialPaginationEnabled(Request $request = null, Operation $operation = null): bool
    {
        $enabled = $this->partialPaginationEnabled;
        $clientEnabled = $this->clientPartialPagination;

        if ($operation) {
            $enabled = $operation->getPaginationPartial() ?? $enabled;

            if ($request) {
                $clientEnabled = $operation->getPaginationClientPartial() ?? $clientEnabled;
            }
        }

        if ($clientEnabled && $request) {
            $enabled = filter_var($this->getPaginationParameter($request, $this->partialPaginationParameterName, $enabled), \FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    protected function isPaginationEnabled(Request $request, Operation $operation): bool
    {
        $enabled = $operation->getPaginationEnabled() ?? $this->paginationOptions->isPaginationEnabled();
        $clientEnabled = $operation->getPaginationClientEnabled() ?? $this->paginationOptions->getPaginationClientEnabled();

        if ($clientEnabled) {
            $enabled = filter_var($this->getPaginationParameter($request, $this->paginationOptions->getPaginationClientEnabledParameterName(), $enabled), \FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getPaginationParameter(Request $request, string $parameterName, $default = null)
    {
        if (null !== $paginationAttribute = $request->attributes->get('_api_pagination')) {
            return \array_key_exists($parameterName, $paginationAttribute) ? $paginationAttribute[$parameterName] : $default;
        }

        return $request->query->all()[$parameterName] ?? $default;
    }
}
