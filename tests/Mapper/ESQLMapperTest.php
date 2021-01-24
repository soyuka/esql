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

namespace Soyuka\ESQL\Mapper\Tests;

use Jane\AutoMapper\AutoMapperInterface;
use Soyuka\ESQL\Bridge\Automapper\ESQLMapper;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\Bridge\Symfony\Serializer\ESQLMapper as ESQLSerializerMapper;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Car;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Category;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Model;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ESQLMapperTest extends KernelTestCase
{
    /**
     * @dataProvider getMapper
     */
    public function testMapCar(ESQLMapperInterface $mapper): void
    {
        ESQL::getAlias(Car::class);
        ESQL::getAlias(Model::class);

        $model = new Model();
        $model->id = 1;
        $model->name = 'Volkswagen';

        $car = new Car();
        $car->id = 1;
        $car->name = 'Caddy';
        $car->model = $model;

        $car2 = new Car();
        $car2->id = 2;
        $car2->name = 'Passat';
        $car2->model = $model;

        $this->assertEquals([$car, $car2], $mapper->map([
            ['car_id' => '1', 'car_name' => 'Caddy', 'model_id' => '1', 'model_name' => 'Volkswagen'],
            ['car_id' => '2', 'car_name' => 'Passat', 'model_id' => '1', 'model_name' => 'Volkswagen'],
        ], Car::class));
    }

    /**
     * @dataProvider getMapper
     */
    public function testMapCategory(ESQLMapperInterface $mapper): void
    {
        ESQL::getAlias(Category::class);

        $vegetables = new Category();
        $vegetables->identifier = 'v';
        $vegetables->parent = null;
        $category = new Category();
        $category->identifier = 'salads';
        $category->name = 'Salads';
        $category->parent = $vegetables;

        $this->assertEquals($category, $mapper->map(['category_identifier' => 'salads', 'category_name' => 'Salads', 'category_parent_id' => 'v'], Category::class));
    }

    public function getMapper(): array
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $autoMapper = $container->get(AutoMapperInterface::class);
        $registry = $container->get('doctrine');
        $normalizer = new ObjectNormalizer();
        $esql = new ESQL($registry);

        return [
            [new ESQLMapper($autoMapper, $esql, $registry)],
            [new ESQLSerializerMapper($normalizer, $esql, $registry)],
        ];
    }
}
