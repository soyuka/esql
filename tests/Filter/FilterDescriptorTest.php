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

namespace Soyuka\ESQL\Tests\Filter;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Soyuka\ESQL\Bridge\ApiPlatform\Filter\FilterDescriptor;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Car;
use Symfony\Component\PropertyInfo\Type;

/**
 * @psalm-suppress UndefinedClass
 */
class FilterDescriptorTest extends TestCase
{
    use ProphecyTrait;

    public function testGetFilterDescription(): void
    {
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Car::class)->willReturn(new ResourceMetadata('car', null, null, [], [], ['esql' => true]));
        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Car::class)->willReturn(new PropertyNameCollection(['id', 'name', 'color', 'price', 'sold', 'model']));
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Car::class, 'id')->willReturn(new PropertyMetadata(new Type('int'), null, true, null, null, null, null, true));
        $propertyMetadataFactory->create(Car::class, 'name')->willReturn(new PropertyMetadata(new Type('string')));
        $propertyMetadataFactory->create(Car::class, 'color')->willReturn(new PropertyMetadata(new Type('string', true)));
        $propertyMetadataFactory->create(Car::class, 'price')->willReturn(new PropertyMetadata(new Type('int')));
        $propertyMetadataFactory->create(Car::class, 'sold')->willReturn(new PropertyMetadata(new Type('bool')));
        $propertyMetadataFactory->create(Car::class, 'model')->willReturn(new PropertyMetadata(new Type('object')));

        $filterDescriptor = new FilterDescriptor($resourceMetadataFactory->reveal(), $propertyNameCollectionFactory->reveal(), $propertyMetadataFactory->reveal());

        $filters = $filterDescriptor->getDescription(Car::class);

        $this->assertEquals(['name', 'color', 'price', 'sold', 'or', 'and'], array_keys($filters));
    }
}
