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

use Soyuka\ESQL\Bridge\ApiPlatform\DataPersister\DataPersister;
use Soyuka\ESQL\Bridge\ApiPlatform\DataProvider\CollectionDataProvider;
use Soyuka\ESQL\Bridge\ApiPlatform\DataProvider\DataPaginator;
use Soyuka\ESQL\Bridge\ApiPlatform\DataProvider\ItemDataProvider;
use Soyuka\ESQL\Bridge\ApiPlatform\Extension\FilterExtension;
use Soyuka\ESQL\Bridge\ApiPlatform\Extension\SortExtension;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\Bridge\Doctrine\ESQLMapper;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()->defaults()->autowire()->autoconfigure();

    $services->set('esql.doctrine', ESQL::class)->alias(ESQLInterface::class, 'esql.doctrine');
    $services->set('esql.data_paginator', DataPaginator::class)
        ->arg('$itemsPerPage', '%api_platform.collection.pagination.items_per_page%')
        ->arg('$maximumItemsPerPage', '%api_platform.collection.pagination.maximum_items_per_page%')
        ->arg('$partialPaginationEnabled', '%api_platform.collection.pagination.partial%')
        ->arg('$clientPartialPagination', '%api_platform.collection.pagination.client_partial%')
        ->arg('$partialPaginationParameterName', '%api_platform.collection.pagination.partial_parameter_name%')
        ->alias(DataPaginator::class, 'esql.data_paginator');

    $services->set('api_platform.doctrine.orm.default.item_data_provider', ItemDataProvider::class);
    $services->set('api_platform.doctrine.orm.default.collection_data_provider', CollectionDataProvider::class)
        ->arg('$collectionExtensions', tagged_iterator('esql.collection_extension'));

    // $services->set('api_platform.doctrine.orm.data_persister', DataPersister::class);

    $services->set('esql.doctrine.mapper', ESQLMapper::class)
        ->alias(ESQLMapperInterface::class, 'esql.doctrine.mapper');

    $services->set('esql.collection_extension.sort', SortExtension::class)
        ->tag('esql.collection_extension');

    $services->set('esql.collection_extension.filters', FilterExtension::class)
        ->tag('esql.collection_extension');
};
