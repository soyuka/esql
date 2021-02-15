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

use InvalidArgumentException;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Car;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Category;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Model;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Aggregate
{
    public Model $model;
}

final class AggregateSimilar
{
    public Model $firstModel;
    public Model $secondModel;
}

final class Dto
{
    public string $model;
}

class ESQLTest extends KernelTestCase
{
    public function testEsqlAliases(): void
    {
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);

        $car = $esql(Car::class);
        $model = $car(Model::class);

        $this->assertEquals($car->alias(), 'car');
        $this->assertEquals($model->alias(), 'car_model');

        $product = $esql(Product::class);
        $category = $product(Category::class);
        $this->assertEquals($product->alias(), 'product');
        $this->assertEquals($category->alias(), 'product_categoryrelation');
    }

    public function testEsqlWrongAlias(): void
    {
        $this->expectErrorMessage(sprintf('%s has no relation with %s.', Product::class, Model::class));
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);

        $product = $esql(Product::class);
        $category = $product(Model::class);
    }

    public function testEsqlCustomAlias(): void
    {
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);

        $statistics = $esql(Product::class, Aggregate::class);
        $category = $statistics(Model::class);

        $this->assertEquals($statistics->alias(), 'aggregate');
        $this->assertEquals($category->alias(), 'aggregate_model');
    }

    public function testEsqlDtoNoType(): void
    {
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);

        $statistics = $esql(Product::class, Dto::class);

        $this->assertEquals($statistics->columns(), 'dto.id as dto_id, dto.name as dto_name, dto.description as dto_description, dto.gtin as dto_gtin, dto.category_id as dto_categoryrelation_identifier');
    }

    public function testEsqlDtoMultipleSameType(): void
    {
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);

        $statistics = $esql(Product::class, AggregateSimilar::class);
        $model = $statistics(Model::class);
        $secondModel = $statistics(Model::class);

        $this->assertEquals('aggregatesimilar_firstmodel', $model->alias());
        $this->assertEquals('aggregatesimilar_secondmodel', $secondModel->alias());

        try {
            $thirdModel = $statistics(Model::class);
        } catch (InvalidArgumentException $e) {
            $this->assertNotNull($e);
        }

        $thirdModel = $statistics(Model::class, 'MyOwnAlias');
        $this->assertEquals('aggregatesimilar_myownalias', $thirdModel->alias());
    }

    public function testEsql(): void
    {
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);

        $car = $esql(Car::class);
        $model = $car(Model::class);

        $query = <<<SQL
        SELECT {$car->columns()}, {$model->columns(['name'])}
        FROM {$car->table()}
        INNER JOIN {$model->table()} ON {$car->join(Model::class)}
        WHERE {$car->identifier()}
        SQL;

        $this->assertSame('SELECT car.id as car_id, car.name as car_name, car.color as car_color, car.price as car_price, car.sold as car_sold, car.model_id as car_model_id, car_model.name as car_model_name
FROM Car car
INNER JOIN Model car_model ON car_model.id = car.model_id
WHERE car.id = :id', $query
        );
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
        $this->assertEquals($car->columns(['name', 'model'], $car::AS_ARRAY), ['car.name as car_name', 'car.model_id as car_model_id']);
        $this->assertEquals($car->columns(null, $car::WITHOUT_ALIASES | $car::WITHOUT_JOIN_COLUMNS), 'car.id, car.name, car.color, car.price, car.sold');
        $this->assertEquals($car->columns(null, $car::WITHOUT_ALIASES | $car::WITHOUT_JOIN_COLUMNS | $car::IDENTIFIERS), 'car.id');
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }
}
