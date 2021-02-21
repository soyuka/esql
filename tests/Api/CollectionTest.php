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

final class CollectionTest extends AbstractTest
{
    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/cars');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => '/cars',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1033,
            'hydra:view' => [
                '@id' => '/cars?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/cars?page=1',
                'hydra:last' => '/cars?page=104',
                'hydra:next' => '/cars?page=2',
            ],
        ]);
    }

    public function testGetCollectionSecondPage(): void
    {
        $response = static::createClient()->request('GET', '/cars?page=2');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => '/cars',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1033,
            'hydra:view' => [
                '@id' => '/cars?page=2',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/cars?page=1',
                'hydra:last' => '/cars?page=104',
                'hydra:next' => '/cars?page=3',
            ],
        ]);
    }

    public function testGetCollectionPartial(): void
    {
        $response = static::createClient()->request('GET', '/cars?partial=true');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => '/cars',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => '/cars?partial=true&page=1',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        $response = static::createClient()->request('GET', '/cars?partial=true&page=4');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => '/cars',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => '/cars?partial=true&page=4',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:previous' => '/cars?partial=true&page=3',
            ],
        ]);
    }

    public function testStatistics(): void
    {
        $response = static::createClient()->request('GET', '/statistics/cars');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/CarStatistics',
            '@id' => '/statistics/cars',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => '/statistics/cars?page=1',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        $response = static::createClient()->request('GET', '/statistics/cars?page=2');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/CarStatistics',
            '@id' => '/statistics/cars',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => '/statistics/cars?page=2',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);
    }
}
