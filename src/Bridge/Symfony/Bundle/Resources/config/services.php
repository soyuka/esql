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

use Soyuka\ESQL\Bridge\Automapper\ESQLMapper;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()->defaults()->autowire()->autoconfigure();

    $services->set('esql.doctrine', ESQL::class)->alias(ESQLInterface::class, 'esql.doctrine');
    $services->set('esql.doctrine.mapper', ESQLMapper::class)
        ->alias(ESQLMapperInterface::class, 'esql.doctrine.mapper');
};
