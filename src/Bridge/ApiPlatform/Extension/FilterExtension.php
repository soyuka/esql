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
use Symfony\Component\HttpFoundation\RequestStack;

final class FilterExtension implements QueryCollectionExtensionInterface
{
    private ResourceMetadataFactoryInterface $resourceMetadataFactory;
    private RequestStack $requestStack;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, RequestStack $requestStack)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->requestStack = $requestStack;
    }

    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $parameters = [], array $context = []): array
    {
        $query .= '';

        return [$query, $parameters];
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return true;
    }
}
