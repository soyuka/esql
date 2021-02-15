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

use ApiPlatform\Core\DataProvider\PaginationOptions;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Exception\InvalidArgumentException;
use Soyuka\ESQL\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DataPaginator
{
    private ManagerRegistry $managerRegistry;
    private RequestStack $requestStack;
    private ResourceMetadataFactoryInterface $resourceMetadataFactory;
    private ESQLMapperInterface $mapper;
    private PaginationOptions $paginationOptions;
    private ?int $itemsPerPage;
    private ?int $maximumItemsPerPage;
    private bool $partialPaginationEnabled;
    private ?string $clientPartialPagination;
    private string $partialPaginationParameterName;
    public const REGEX_LAST_SELECT = '~SELECT(?!.*SELECT)~is';
    public const ORDER_BY = 'esql_order_by';
    public const ESQL = 'esql';

    public function __construct(RequestStack $requestStack, ManagerRegistry $managerRegistry, ResourceMetadataFactoryInterface $resourceMetadataFactory, ESQLMapperInterface $mapper, PaginationOptions $paginationOptions, ?int $itemsPerPage = 30, ?int $maximumItemsPerPage = null, bool $partialPaginationEnabled = false, ?string $clientPartialPagination = null, string $partialPaginationParameterName = 'partial')
    {
        $this->requestStack = $requestStack;
        $this->managerRegistry = $managerRegistry;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->mapper = $mapper;
        $this->paginationOptions = $paginationOptions;
        $this->itemsPerPage = $itemsPerPage;
        $this->maximumItemsPerPage = $maximumItemsPerPage;
        $this->partialPaginationEnabled = $partialPaginationEnabled;
        $this->clientPartialPagination = $clientPartialPagination;
        $this->partialPaginationParameterName = $partialPaginationParameterName;
    }

    public function shouldPaginate(string $resourceClass, ?string $operationName = null): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        return null !== $request && ($this->isPaginationEnabled($request, $resourceMetadata, $operationName) || $this->isPartialPaginationEnabled(
            $request,
            $resourceMetadata,
            $operationName
        ));
    }

    public function paginate(string $query, string $resourceClass, ?string $operationName = null, array $parameters = [], array $context = []): PartialPaginatorInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new RuntimeException('Not in a request');
        }

        if (!isset($context[self::ESQL]) || !$context[self::ESQL] instanceof ESQLInterface) {
            throw new RuntimeException('No alias to map to');
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $isPartialEnabled = $this->isPartialPaginationEnabled(
            $request,
            $resourceMetadata,
            $operationName
        );

        $itemsPerPage = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_items_per_page', $this->itemsPerPage, true);
        if ($resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $this->paginationOptions->getClientItemsPerPage(), true)) {
            $maxItemsPerPage = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_maximum_items_per_page', $this->maximumItemsPerPage, true);
            $itemsPerPage = (int) $this->getPaginationParameter($request, $this->paginationOptions->getItemsPerPageParameterName() ?: 'itemsPerPage', $itemsPerPage);
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
        $totalItems = $isPartialEnabled ? -1 : $this->count($query, $parameters, $context);
        $nextResult = $firstResult + $itemsPerPage;

        $driverName = $this->managerRegistry->getConnection()->getDriver()->getName();
        switch ($driverName) {
            case 'pdo_sqlsrv':
                $orderBy = $context[self::ORDER_BY] ?? $context[self::ESQL]->columns(null, ESQLInterface::IDENTIFIERS | ESQLInterface::WITHOUT_ALIASES | ESQLInterface::WITHOUT_JOIN_COLUMNS);
                $query = preg_replace(self::REGEX_LAST_SELECT, "SELECT * FROM (SELECT ROW_NUMBER() OVER(ORDER BY {$orderBy}) AS RowNumber,", $query, 1);
                $query = <<<SQL
$query
) AS paginated
WHERE RowNumber BETWEEN $firstResult AND $nextResult
SQL;
                break;
            default:
            $query = $query.' LIMIT '.$itemsPerPage.' OFFSET '.$firstResult;
        }

        $connection = $this->managerRegistry->getConnection();
        $stmt = $connection->prepare($query);
        $stmt->execute($parameters);
        $data = $stmt->fetchAll();

        if ($data) {
            $data = $context[self::ESQL]->map($data);
        }

        return $isPartialEnabled ? new PartialPaginator($data, $page, $itemsPerPage) : new Paginator($data, $page, $itemsPerPage, $totalItems);
    }

    protected function count(string $query, array $parameters = [], array $context = []): float
    {
        $connection = $this->managerRegistry->getConnection();
        $driverName = $this->managerRegistry->getConnection()->getDriver()->getName();

        Context::setMode('NO_ENCLOSING_QUOTES');
        $parser = new Parser($query);
        if (\count($parser->errors) || !isset($parser->statements[0]) || !$parser->statements[0] instanceof SelectStatement) {
            switch ($driverName) {
                case 'pdo_sqlsrv':
                if (!isset($context[self::ORDER_BY])) {
                    throw new RuntimeException('An ORDER_BY clause is needed to use ROW_NUMBER');
                }

                $query = preg_replace(self::REGEX_LAST_SELECT, "SELECT MAX(RowNumber) as _esql_count FROM (SELECT ROW_NUMBER() OVER(ORDER BY {$context[self::ORDER_BY]}) AS RowNumber,", $query, 1);
                $query = <<<SQL
$query
) AS paginated
SQL;
                    break;
                case 'pdo_pgsql':
                    $query = preg_replace(self::REGEX_LAST_SELECT, 'SELECT COUNT(1) OVER () AS _esql_count,', $query, 1);
                    break;
                default:
                    $query = preg_replace(self::REGEX_LAST_SELECT, 'SELECT COUNT(1) AS _esql_count,', $query, 1);
            }
        } else {
            $statement = $parser->statements[0];
            $statement->expr = [new Expression('COUNT(1)', '_esql_count')];

            switch ($driverName) {
                case 'pdo_pgsql':
                    // Use a window with postgresql
                    if ($statement->order) {
                        $statement->expr = [new Expression('COUNT(1) OVER ()', '_esql_count')];
                    }
                    $query = $statement->build().' LIMIT 1';
                    break;
                default:
                    $query = $statement->build();
            }
        }

        $stmt = $connection->prepare($query);
        $stmt->execute($parameters);
        ['_esql_count' => $totalItems] = $stmt->fetch();

        return (float) $totalItems;
    }

    protected function isPartialPaginationEnabled(Request $request = null, ResourceMetadata $resourceMetadata = null, string $operationName = null): bool
    {
        $enabled = $this->partialPaginationEnabled;
        $clientEnabled = $this->clientPartialPagination;

        if ($resourceMetadata) {
            $enabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_partial', $enabled, true);

            if ($request) {
                $clientEnabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_partial', $clientEnabled, true);
            }
        }

        if ($clientEnabled && $request) {
            $enabled = filter_var($this->getPaginationParameter($request, $this->partialPaginationParameterName, $enabled), \FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    protected function isPaginationEnabled(Request $request, ResourceMetadata $resourceMetadata, string $operationName = null): bool
    {
        $enabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_enabled', $this->paginationOptions->isPaginationEnabled(), true);
        $clientEnabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_enabled', $this->paginationOptions->getPaginationClientEnabled(), true);

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
