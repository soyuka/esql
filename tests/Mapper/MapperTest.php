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

namespace Soyuka\ESQL\Tests;

use Jane\AutoMapper\AutoMapperInterface;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\Mapper\Mapper;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Car;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Model;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MapperTest extends KernelTestCase
{
    public function testMapCar(): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $autoMapper = $container->get(AutoMapperInterface::class);
        $registry = $container->get('doctrine');
        ESQL::getAlias(Car::class);
        ESQL::getAlias(Model::class);

        $mapper = new Mapper($autoMapper, $registry);
        dump($mapper->map([
            ['car_id' => '1', 'car_name' => 'Caddy', 'model_id' => '1', 'model_name' => 'Volkswagen'],
            ['car_id' => '2', 'car_name' => 'Passat', 'model_id' => '1', 'model_name' => 'Volkswagen'],
        ], Car::class));
    }
}
