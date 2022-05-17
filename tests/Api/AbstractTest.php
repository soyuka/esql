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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Usually we use the Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait but I needed to override `purgeWithTruncate` as there's
 * an issue with TRUNCATE and FOREIGN KEYs on SQL Server see https://github.com/doctrine/data-fixtures/issues/113#issuecomment-144950542.
 */
abstract class AbstractTest extends ApiTestCase
{
    /**
     * @var string|null The name of the Doctrine manager to use
     */
    protected static $manager;

    /**
     * @var string[] The list of bundles where to look for fixtures
     */
    protected static $bundles = [];

    /**
     * @var bool Append fixtures instead of purging
     */
    protected static $append = false;

    /**
     * @var bool Use TRUNCATE to purge
     */
    protected static $purgeWithTruncate = false;

    /**
     * @var string|null The name of the Doctrine shard to use
     */
    protected static $shard;

    /**
     * @var string|null The name of the Doctrine connection to use
     */
    protected static $connection;

    /**
     * @var array|null Contain loaded fixture from alice
     */
    protected static $fixtures;

    protected static function populateDatabase(): void
    {
        $container = static::$container ?? static::$kernel->getContainer();
        static::$fixtures = $container->get('hautelook_alice.loader')->load(
            new Application(static::$kernel), // OK this is ugly... But there is no other way without redesigning LoaderInterface from the ground.
            $container->get('doctrine')->getManager(static::$manager),
            static::$bundles,
            static::$kernel->getEnvironment(),
            static::$append,
            static::$purgeWithTruncate,
            static::$shard
        );
    }

    protected static $dbPopulated = false;

    protected static function bootKernel(array $options = []): KernelInterface
    {
        $kernel = parent::bootKernel($options);

        $container = static::$container ?? static::$kernel->getContainer();
        $doctrine = $container->get('doctrine')->getConnection(static::$connection);

        if (!static::$dbPopulated) {
            static::populateDatabase();
            static::$dbPopulated = true;
        }

        $doctrine->beginTransaction();

        return $kernel;
    }

    protected static function ensureKernelShutdown(): void
    {
        $container = static::$container ?? null;
        if (null === $container && null !== static::$kernel) {
            $container = static::$kernel->getContainer();
        }

        if (null !== $container) {
            $connection = $container->get('doctrine')->getConnection(static::$connection);
            if ($connection->isTransactionActive()) {
                $connection->rollback();
            }
        }

        parent::ensureKernelShutdown();
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function getDoctrine()
    {
        return self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
    }
}
