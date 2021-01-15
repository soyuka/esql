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
// api/tests/BooksTest.php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

class ApiCollectionTest extends ApiTestCase
{
    use RefreshDatabaseTrait;

    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/cars');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => '/cars',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 30,
            'hydra:view' => [
                '@id' => '/cars?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/cars?page=1',
                'hydra:last' => '/cars?page=3',
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
            'hydra:totalItems' => 30,
            'hydra:view' => [
                '@id' => '/cars?page=2',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/cars?page=1',
                'hydra:last' => '/cars?page=3',
                'hydra:next' => '/cars?page=3',
            ],
        ]);
    }

    public function testGetCollectionSortByName(): void
    {
        $response = static::createClient()->request('GET', '/cars?sort=name.asc');
        $this->assertResponseIsSuccessful();
        $this->assertEquals('a', $response->toArray()['hydra:member'][0]['name'][0]);
        $response = static::createClient()->request('GET', '/cars?sort=name.desc');
        $this->assertResponseIsSuccessful();
        $this->assertEquals('c', $response->toArray()['hydra:member'][0]['name'][0]);
    }

    public function testGetCollectionSortByNameAndColor(): void
    {
        $response = static::createClient()->request('GET', '/cars?sort=color.asc.nullsfirst');
        $this->assertResponseIsSuccessful();
        $this->assertNull($response->toArray()['hydra:member'][0]['color']);
        $response = static::createClient()->request('GET', '/cars?sort=color.asc.nullslast');
        $this->assertResponseIsSuccessful();
        $this->assertIsString($response->toArray()['hydra:member'][0]['color']);
        $response = static::createClient()->request('GET', '/cars?sort=name.asc,color.nullsfirst');
        $this->assertResponseIsSuccessful();
        $this->assertEquals('a', $response->toArray()['hydra:member'][0]['name'][0]);
    }
}
