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

use ApiPlatform\Core\DataProvider\PaginationOptions;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class PaginationExtension implements QueryCollectionExtensionInterface
{
    private ManagerRegistry $managerRegistry;
    private RequestStack $requestStack;
    private ResourceMetadataFactoryInterface $resourceMetadataFactory;
    private PaginationOptions $paginationOptions;
    private ?int $itemsPerPage;
    private ?int $maximumItemsPerPage;

    public function __construct(RequestStack $requestStack, ManagerRegistry $managerRegistry, ResourceMetadataFactoryInterface $resourceMetadataFactory, PaginationOptions $paginationOptions, ?int $itemsPerPage = 30, ?int $maximumItemsPerPage = null)
    {
        $this->requestStack = $requestStack;
        $this->managerRegistry = $managerRegistry;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->paginationOptions = $paginationOptions;
        $this->itemsPerPage = $itemsPerPage;
        $this->maximumItemsPerPage = $maximumItemsPerPage;
    }

    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $context = []): string
    {
        $isPartialEnabled = $this->isPartialPaginationEnabled(
            $request = $this->requestStack->getCurrentRequest(),
            $this->resourceMetadataFactory->create($resourceClass),
            $operationName
        );

        if (null === $request) {
            throw new LogicException('Not in a request');
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
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

        return $query.' LIMIT '.$itemsPerPage.' OFFSET '.$firstResult;
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            return false;
        }

        return $this->isPaginationEnabled($request, $this->resourceMetadataFactory->create($resourceClass), $operationName);
    }

    private function isPartialPaginationEnabled(Request $request = null, ResourceMetadata $resourceMetadata = null, string $operationName = null): bool
    {
        // $enabled = $this->partial;
        // $clientEnabled = $this->clientPartial;
        //
        // if ($resourceMetadata) {
        //     $enabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_partial', $enabled, true);
        //
        //     if ($request) {
        //         $clientEnabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_partial', $clientEnabled, true);
        //     }
        // }

        // if ($clientEnabled && $request) {
        //     $enabled = filter_var($this->getPaginationParameter($request, $this->partialParameterName, $enabled), FILTER_VALIDATE_BOOLEAN);
        // }

        // return $enabled;
        return false;
    }

    private function isPaginationEnabled(Request $request, ResourceMetadata $resourceMetadata, string $operationName = null): bool
    {
        $enabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_enabled', $this->paginationOptions->isPaginationEnabled(), true);
        $clientEnabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_enabled', $this->paginationOptions->getPaginationClientEnabled(), true);

        if ($clientEnabled) {
            $enabled = filter_var($this->getPaginationParameter($request, $this->paginationOptions->getPaginationClientEnabledParameterName(), $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    private function getPaginationParameter(Request $request, string $parameterName, $default = null)
    {
        if (null !== $paginationAttribute = $request->attributes->get('_api_pagination')) {
            return \array_key_exists($parameterName, $paginationAttribute) ? $paginationAttribute[$parameterName] : $default;
        }

        return $request->query->all()[$parameterName] ?? $default;
    }
}
