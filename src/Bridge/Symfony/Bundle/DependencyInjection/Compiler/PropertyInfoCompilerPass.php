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

namespace Soyuka\ESQL\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// use Symfony\Component\DependencyInjection\Reference;

/**
 * Removes doctrine from symfony's property info to make ids writable with jane-php/automapper.
 *
 * @internal
 */
final class PropertyInfoCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('doctrine.orm.default_entity_manager.property_info_extractor');
        $definition->setTags([]);
        $container->setDefinition('doctrine.orm.default_entity_manager.property_info_extractor', $definition);
    }
}
