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

namespace Soyuka\ESQL\Bridge\ApiPlatform\Extension;

interface QueryCollectionExtensionInterface
{
    public function apply(string $query, string $resourceClass, ?string $operationName = null, array $parameters = [], array $context = []): array;

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool;
}
