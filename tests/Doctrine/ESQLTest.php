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

use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Car;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Model;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ESQLTest extends KernelTestCase
{
    public function testEsql(): void
    {
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);

        $car = $esql(Car::class);
        $model = $car(Model::class);

        $query = <<<SQL
        SELECT {$car->columns()}, {$model->columns()}
        FROM {$car->table()}
        INNER JOIN {$model->table()} ON {$car->join(Model::class)}
        WHERE {$car->identifier()}
        SQL;

        $this->assertSame('SELECT car.id as car_id, car.name as car_name, car.color as car_color, car.price as car_price, car.sold as car_sold, car_model.id as car_model_id, car_model.name as car_model_name
FROM Car car
INNER JOIN Model car_model ON car_model.id = car.model_id
WHERE car.id = :id', $query
        );
        $this->assertEquals($esql->getAlias(Car::class), 'car');
        $this->assertEquals($esql->getAlias(Model::class), 'model');
    }

    public function testFilterColumns(): void
    {
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);

        $car = $esql(Car::class);

        $this->assertEquals($car->columns(['name']), 'car.name as car_name');
        $this->assertEquals($car->columns(['name', 'price']), 'car.name as car_name, car.price as car_price');
    }

    public function testColumnsOutput(): void
    {
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);

        $car = $esql(Car::class);

        $this->assertEquals($car->columns(['name'], $car::AS_ARRAY), ['car.name as car_name']);
        $this->assertEquals($car->columns(['name', 'price'], $car::AS_ARRAY), ['car.name as car_name', 'car.price as car_price']);
        $this->assertEquals($car->columns(['name', 'price'], $car::AS_STRING | $car::WITHOUT_ALIASES), 'car.name, car.price');
        $this->assertEquals($car->columns(['name', 'price'], $car::AS_ARRAY | $car::WITHOUT_ALIASES), ['car.name', 'car.price']);
        $this->assertEquals($car->columns(['name', 'model'], $car::AS_ARRAY | $car::WITH_JOIN_COLUMNS), ['car.name as car_name', 'car.model_id as car_model_id']);
        $this->assertEquals($car->columns(['name', 'model'], $car::WITHOUT_ALIASES | $car::WITH_JOIN_COLUMNS), 'car.name, car.model_id');
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }
}
