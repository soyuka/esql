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
use Soyuka\ESQL\Bridge\Symfony\Bundle\ESQLBundle;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\TestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Hautelook\AliceBundle\HautelookAliceBundle;
use Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle;
use Nelmio\Alice\Bridge\Symfony\NelmioAliceBundle;
use Jane\Bundle\AutoMapperBundle\JaneAutoMapperBundle;

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
            new NelmioAliceBundle(),
            new FidryAliceDataFixturesBundle(),
            new HautelookAliceBundle(),
            new JaneAutoMapperBundle(),
            new TestBundle(),
        ];
    }

    public function getProjectDir()
    {
        return __DIR__;
    }

    /**
     * @param \Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator|\Symfony\Component\Routing\RouteCollectionBuilder $routes
     */
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
            case 'sqlsrv':
                $loader->load(__DIR__.'/config/config_sqlsrv.yml');
                break;
            default:
                $loader->load(__DIR__.'/config/config_sqlite.yml');
                break;
        }
    }
}
