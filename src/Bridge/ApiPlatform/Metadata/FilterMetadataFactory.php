<?php

namespace Soyuka\ESQL\Bridge\ApiPlatform\Metadata;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

final class FilterMetadataFactory implements ResourceMetadataFactoryInterface
{
    private ResourceMetadataFactoryInterface $decorated;
    public function __construct(ResourceMetadataFactoryInterface $decorated) 
    {
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);

        if (!$resourceMetadata->getAttribute('esql')) {
            return $resourceMetadata;
        }

        $operations = $resourceMetadata->getCollectionOperations() ?: [];

        foreach ($operations as $key => $operation) {
            if ('GET' === $operation['method'] ?? 'GET')
            $operations[$key]['filters'][] = 'esql.filter_descriptor';
        }

        return $resourceMetadata->withCollectionOperations($operations);
    }
}
