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
final class FilterExtensionTest extends ApiTestCase
{
    use RefreshDatabaseTrait;

    public function testSimpleFilter(): void
    {
        $response = static::createClient()->request('GET', '/cars?sold=eq.true');
        $this->assertResponseIsSuccessful();
        dump($response->toArray()['hydra:member']);
    }

    // public function testComplexFilter(): void
    // {
    //     $response = static::createClient()->request('GET', '/cars?and=(price.gt.1000,sold.is.false,or(name.not.eq.caddy,sold.is.true))');
    //     $this->assertResponseIsSuccessful();
    //     dump($response->toArray()['hydra:member']);
    // }
}
