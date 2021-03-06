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

namespace Soyuka\ESQL\Bridge\Symfony\Bundle;

use Soyuka\ESQL\Bridge\Symfony\Bundle\DependencyInjection\Compiler\PropertyInfoCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The Symfony bundle.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ESQLBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new PropertyInfoCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
    }
}
