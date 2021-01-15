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

use ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Jane\AutoMapper\Bundle\JaneAutoMapperBundle;
use Soyuka\ESQL\Bridge\Symfony\Bundle\ESQLBundle;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\TestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new ApiPlatformBundle(),
            new ESQLBundle(),
            new Nelmio\Alice\Bridge\Symfony\NelmioAliceBundle(),
            new Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle(),
            new Hautelook\AliceBundle\HautelookAliceBundle(),
            new JaneAutoMapperBundle(),
            new TestBundle(),
        ];
    }

    public function getProjectDir()
    {
        return __DIR__;
    }

    protected function configureRoutes($routes): void
    {
        $routes->import(__DIR__.'/config/routing.yml');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->setParameter('kernel.project_dir', __DIR__);

        $loader->load(__DIR__.'/../../../vendor/api-platform/core/src/Bridge/Symfony/Bundle/Resources/config/openapi.xml');
        $loader->load(__DIR__.'/../../../vendor/api-platform/core/src/Bridge/Symfony/Bundle/Resources/config/json_schema.xml');

        switch ($_SERVER['ESQL_DB'] ?? null) {
            case 'postgres':
                $loader->load(__DIR__.'/config/config_postgres.yml');
                break;
            default:
                $loader->load(__DIR__.'/config/config_sqlite.yml');
        }
    }
}
