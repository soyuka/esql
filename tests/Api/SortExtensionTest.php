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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

/**
 * @psalm-suppress MissingDependency
 */
final class SortExtensionTest extends ApiTestCase
{
    use RefreshDatabaseTrait;

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
        $response = static::createClient()->request('GET', '/cars?sort=name.asc,color.desc');
        $this->assertResponseIsSuccessful();
        $this->assertEquals('a', $response->toArray()['hydra:member'][0]['name'][0]);
    }

    public function testGetCollectionSortByNameAndColorNulls(): void
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

    public function testGetCollectionSortByPrice(): void
    {
        $response = static::createClient()->request('GET', '/cars?sort=price.desc');
        $this->assertResponseIsSuccessful();
    }

}
