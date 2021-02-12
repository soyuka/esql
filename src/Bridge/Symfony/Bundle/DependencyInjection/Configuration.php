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

namespace Soyuka\ESQL\Bridge\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle;
use Jane\Component\AutoMapper\AutoMapper;
use Soyuka\ESQL\Bridge\Automapper\ESQLMapper;
use Soyuka\ESQL\Bridge\Symfony\Serializer\ESQLMapper as SerializerESQLMapper;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('esql');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('mapper')
                    ->defaultValue(class_exists(AutoMapper::class) ? ESQLMapper::class : SerializerESQLMapper::class)
                ->end()
                ->arrayNode('api-platform')
                    ->canBeDisabled()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultValue(class_exists(ApiPlatformBundle::class))
                        ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
