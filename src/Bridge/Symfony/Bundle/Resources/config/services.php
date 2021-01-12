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
use Soyuka\ESQL\Bridge\ApiPlatform\DataProvider\ItemDataProvider;
use Soyuka\ESQL\Bridge\ApiPlatform\Extension\PaginationExtension;
use Soyuka\ESQL\Bridge\Doctrine\PropertyInfoExtractor;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\ESQLInterface;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()->defaults()->autowire()->autoconfigure();

    $services->set('esql.doctrine', ESQL::class)->alias(ESQLInterface::class, 'esql.doctrine');
    $services->set('api_platform.doctrine.orm.default.item_data_provider', ItemDataProvider::class);
    $services->set('api_platform.doctrine.orm.default.collection_data_provider', CollectionDataProvider::class)
        ->arg('$collectionExtensions', tagged_iterator('esql.collection_extension'));
    $services->set('api_platform.doctrine.orm.data_persister', DataPersister::class);
    $services->set('doctrine.orm.default_entity_manager.property_info_extractor', PropertyInfoExtractor::class);
    $services->set('esql.collection_extension.pagination', PaginationExtension::class)
        ->tag('esql.collection_extension');
};
