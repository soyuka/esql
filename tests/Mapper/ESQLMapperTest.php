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

use Doctrine\Persistence\ManagerRegistry;
use Jane\Component\AutoMapper\AutoMapperInterface;
use Soyuka\ESQL\Bridge\Automapper\ESQLMapper;
use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\Bridge\Symfony\Serializer\ESQLMapper as ESQLSerializerMapper;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Car;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Category;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Model;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Uid\Ulid;

class DTO
{
    public string $id;
    public string $name;
}

class ESQLMapperTest extends KernelTestCase
{
    /**
     * @dataProvider getMapper
     */
    public function testMapCar(ESQLMapperInterface $mapper, ManagerRegistry $registry): void
    {
        $esql = new ESQL($registry, $mapper);
        $c = $esql(Car::class);
        $m = $c(Model::class);

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

        $this->assertEquals([$car, $car2], $c->map([
            ['car_id' => '1', 'car_name' => 'Caddy', 'car_model_id' => '1', 'car_model_name' => 'Volkswagen'],
            ['car_id' => '2', 'car_name' => 'Passat', 'car_model_id' => '1', 'car_model_name' => 'Volkswagen'],
        ]));
    }

    /**
     * @dataProvider getMapper
     */
    public function testMapCategory(ESQLMapperInterface $mapper, ManagerRegistry $registry): void
    {
        $esql = new ESQL($registry, $mapper);
        $c = $esql(Category::class);

        $vegetables = new Category();
        $vegetables->identifier = 'v';
        $vegetables->parent = null;
        $category = new Category();
        $category->identifier = 'salads';
        $category->name = 'Salads';
        $category->parent = $vegetables;

        $this->assertEquals($category, $c->map(['category_identifier' => 'salads', 'category_name' => 'Salads', 'category_parent_identifier' => 'v']));
    }

    /**
     * @dataProvider getMapper
     */
    public function testMapProduct(ESQLMapperInterface $mapper, ManagerRegistry $registry): void
    {
        $esql = new ESQL($registry, $mapper);
        $p = $esql(Product::class);
        $c = $p(Category::class);

        $category = new Category();
        $category->identifier = 'salads';
        $category->name = 'Salads';
        $category->parent = null;

        $product = new Product();
        $product->setId((string) new Ulid());
        $product->name = 'tomato';
        $product->description = 'a red tomato';
        $product->categoryRelation = $category;
        $product->gtin = 'ASDGJ499190AA';

        $this->assertEquals($product, $p->map([
            'product_id' => $product->getId(),
            'product_name' => 'tomato',
            'product_description' => 'a red tomato',
            'product_categoryrelation_identifier' => $category->identifier,
            'product_gtin' => $product->gtin,
            'product_categoryrelation_name' => $category->name,
            'product_categoryrelation_parent' => null,
        ]));
    }

    /**
     * @dataProvider getMapper
     */
    public function testMapTo(ESQLMapperInterface $mapper, ManagerRegistry $registry): void
    {
        $esql = new ESQL($registry, $mapper);
        $p = $esql(Product::class, DTO::class);

        $product = new Product();
        $product->setId((string) new Ulid());
        $product->name = 'tomato';
        $product->description = 'a red tomato';
        $product->gtin = 'ASDGJ499190AA';

        $dto = new DTO();
        $dto->id = $product->getId();
        $dto->name = $product->name;

        $this->assertEquals($dto, $p->map([
            'product_id' => $product->getId(),
            'product_name' => 'tomato',
        ]));
    }

    public function getMapper(): array
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $autoMapper = $container->get(AutoMapperInterface::class);
        $registry = $container->get('doctrine');
        $normalizer = new ObjectNormalizer();

        return [
            [new ESQLMapper($autoMapper), $registry],
            [new ESQLSerializerMapper($normalizer), $registry],
        ];
    }
}
