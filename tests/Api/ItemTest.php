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

final class ItemTest extends AbstractTest
{
    public function testGetItem(): void
    {
        $client = static::createClient();
        $response = $client->request('GET', '/cars');
        $cars = $response->toArray();
        $cars = $cars['hydra:member'];
        $iri = $cars[0]['@id'];
        $response = $client->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => $iri,
        ]);
    }
}
