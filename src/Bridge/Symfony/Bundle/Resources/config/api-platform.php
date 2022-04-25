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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Soyuka\ESQL\Bridge\ApiPlatform\Extension\FilterExtension;
use Soyuka\ESQL\Bridge\ApiPlatform\Extension\QueryCollectionExtensionInterface;
use Soyuka\ESQL\Bridge\ApiPlatform\Extension\SortExtension;
use Soyuka\ESQL\Bridge\ApiPlatform\Filter\FilterDescriptor;
use Soyuka\ESQL\Bridge\ApiPlatform\Metadata\FilterMetadataFactory;
use Soyuka\ESQL\Bridge\ApiPlatform\State\CollectionProvider;
use Soyuka\ESQL\Bridge\ApiPlatform\State\DataPaginator;
use Soyuka\ESQL\Bridge\ApiPlatform\State\ItemProvider;
use Soyuka\ESQL\Bridge\ApiPlatform\State\Provider;
use Soyuka\ESQL\Filter\FilterParser;
use Soyuka\ESQL\Filter\FilterParserInterface;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()->defaults()->autowire()->autoconfigure();

    $services->set('esql.data_paginator', DataPaginator::class)
        ->arg('$itemsPerPage', '%api_platform.collection.pagination.items_per_page%')
        ->arg('$maximumItemsPerPage', '%api_platform.collection.pagination.maximum_items_per_page%')
        ->arg('$partialPaginationEnabled', '%api_platform.collection.pagination.partial%')
        ->arg('$clientPartialPagination', '%api_platform.collection.pagination.client_partial%')
        ->arg('$partialPaginationParameterName', '%api_platform.collection.pagination.partial_parameter_name%')
        ->alias(DataPaginator::class, 'esql.data_paginator');

    $services->set('esql.api_platform.default.item_provider', ItemProvider::class)
        ->tag('api_platform.state_provider');
    $services->set('esql.api_platform.default.collection_provider', CollectionProvider::class)
        ->tag('api_platform.state_provider')
        ->arg('$collectionExtensions', tagged_iterator('esql.collection_extension'));

    $services->set(Provider::class, Provider::class)
             ->tag('api_platform.state_provider')
             ->arg('$itemProvider', service('esql.api_platform.default.item_provider'))
             ->arg('$collectionProvider', service('esql.api_platform.default.collection_provider'));

    $services->alias('esql.api_platform.default.provider', Provider::class);

    $services
        ->instanceof(QueryCollectionExtensionInterface::class)
            ->tag('esql.collection_extension');

    $services->set('esql.collection_extension.sort', SortExtension::class)
        ->tag('esql.collection_extension');

    $services->set('esql.collection_extension.filters', FilterExtension::class)
        ->tag('esql.collection_extension');

    $services->set('esql.filter.parser', FilterParser::class)->alias(FilterParserInterface::class, 'esql.filter.parser');
    $services->set('esql.filter_descriptor', FilterDescriptor::class)->tag('api_platform.filter');
    // $services->set('esql.filter_metadata', FilterMetadataFactory::class)->decorate('api_platform.metadata.resource.metadata_factory')->arg('$decorated', service('.inner'));
};
