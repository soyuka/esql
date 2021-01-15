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

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

/**
 * @psalm-suppress MissingDependency
 */
final class ItemTest extends ApiTestCase
{
    use RefreshDatabaseTrait;

    public function testGetItem(): void
    {
        $response = static::createClient()->request('GET', '/cars');
        $cars = $response->toArray()['hydra:member'];
        $iri = $cars[0]['@id'];
        $response = static::createClient()->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => $iri,
        ]);
    }
}
