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

final class FilterExtensionTest extends AbstractTest
{
    public function testSimpleFilter(): void
    {
        $response = static::createClient()->request('GET', '/cars?sold=eq.true');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => '/cars',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                ['name' => 'golf', 'sold' => true],
                ['name' => 'caddy', 'sold' => true],
            ],
            'hydra:totalItems' => 2,
        ]);
    }

    public function testComplexFilter(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        $registry = $container->get('doctrine');

        if (\in_array($registry->getConnection()->getDriver()->getName(), ['pdo_sqlite', 'pdo_sqlsrv'], true)) {
            $this->markTestSkipped();
        }

        $response = static::createClient()->request('GET', '/cars?and=(price.gt.1000000,sold.is.false,or(sold.is.true))&sort=price.asc');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Car',
            '@id' => '/cars',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                ['name' => 'golf', 'sold' => true, 'price' => 10000],
                ['name' => 'caddy', 'sold' => true, 'price' => 1000000],
                ['name' => 'passat', 'sold' => false, 'price' => 2599999],
            ],
            'hydra:totalItems' => 3,
        ]);
    }
}
