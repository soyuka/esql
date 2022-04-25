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

namespace Soyuka\ESQL\Bridge\ApiPlatform\Metadata;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

final class FilterMetadataFactory implements ResourceMetadataFactoryInterface
{
    public function __construct(private readonly ResourceMetadataFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);

        if (!$resourceMetadata->getAttribute('esql')) {
            return $resourceMetadata;
        }

        $operations = $resourceMetadata->getCollectionOperations() ?: [];

        foreach ($operations as $key => $operation) {
            if ('GET' === $operation['method'] ?? 'GET') {
                $operations[$key]['filters'][] = 'esql.filter_descriptor';
            }
        }

        return $resourceMetadata->withCollectionOperations($operations);
    }
}
