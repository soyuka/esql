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
use Soyuka\ESQL\Bridge\Doctrine\ESQL;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set('esql.doctrine', ESQL::class);
    $services->set('api_platform.doctrine.orm.default.item_data_provider', ItemDataProvider::class);
    $services->set('api_platform.doctrine.orm.default.collection_data_provider', CollectionDataProvider::class);
    $services->set('api_platform.doctrine.orm.data_persister', DataPersister::class);

    // $services->set('property_info', PropertyInfoExtractor::class)->args([[], [], [], [], []]);
};
