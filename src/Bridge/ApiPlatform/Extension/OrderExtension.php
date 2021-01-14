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
use LogicException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;

final class OrderExtension implements QueryCollectionExtensionInterface
{
    private ResourceMetadataFactoryInterface $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $parameters = [], array $context = []): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $defaultOrder = $resourceMetadata->getCollectionOperationAttribute($operationName, 'order', [], true);

        // TODO: deprecate this as it has no effects
        if (\is_string($defaultOrder)) {
            return [$query, $parameters];
        }

        $parser = new Parser($query);
        $statement = $parser->statements[0];

        if (!$statement instanceof SelectStatement) {
            throw new LogicException('No select statement found, can not order.');
        }

        $alias = $statement->from[0]->alias;

        $orders = [];
        foreach ($defaultOrder as $field => $order) {
            if (\is_int($field)) {
                // Default direction
                $field = $order;
                $order = 'ASC';
            }

            $orders[] = $alias ? "$alias.$field $order" : "$field $order";
        }

        return [$orders ? $query.' ORDER BY '.implode(', ', $orders) : $query, $parameters];
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return null !== $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order');
    }
}
