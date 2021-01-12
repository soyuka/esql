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

// use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;

final class OrderExtension implements QueryCollectionExtensionInterface
{
    private ResourceMetadataFactoryInterface $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $context = []): string
    {
        $defaultOrder = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order');
        $orders = [];
        foreach ($defaultOrder as $field => $order) {
            if (\is_int($field)) {
                // Default direction
                $field = $order;
                $order = 'ASC';
            }

            $orders[] = "$field $order";
        }

        return $query.' ORDER BY '.implode(', ', $orders);
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return null !== $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order');
    }
}
