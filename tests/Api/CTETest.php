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

namespace App\Tests\Api;

final class CTETest extends AbstractTest
{
    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/products');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Product',
            '@id' => '/products',
            '@type' => 'hydra:Collection',
        ]);
    }

    /**
     * @dataProvider categoryFilters
     */
    public function testGetCollectionCategoryFilter(string $filter, string $regex): void
    {
        $response = static::createClient()->request('GET', '/products?category='.$filter);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Product',
            '@id' => '/products',
            '@type' => 'hydra:Collection',
        ]);

        foreach ($response->toArray()['hydra:member'] as $product) {
            $this->assertMatchesRegularExpression("~$regex~", $product['category']);
        }
    }

    public function categoryFilters(): iterable
    {
        yield ['v', 'Vegetables'];
        yield ['bagged_salads', 'Vegetables / Bagged salads'];
        yield ['iceberg', 'Vegetables / Bagged salads / Iceberg'];
    }
}
