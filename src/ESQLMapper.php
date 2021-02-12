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

namespace Soyuka\ESQL;

abstract class ESQLMapper implements ESQLMapperInterface
{
    abstract public function map(array $data, string $class);

    // Todo: find an abstraction for aliases management
    protected function toArray(array $data, array &$memory = []): array
    {
        foreach ($data as $key => $value) {
            $aliasPos = strpos($key, '_');
            if (false === $aliasPos) {
                continue;
            }

            $alias = substr($key, 0, $aliasPos);
            $key = substr($key, $aliasPos + 1);
            $relationPos = strpos($key, '_');
            if (!$relationPos) {
                $memory[$key] = $value;
                continue;
            }

            $nextAlias = substr($key, 0, $relationPos);
            if (!isset($memory[$nextAlias])) {
                $memory[$nextAlias] = [];
            }

            $memory[$nextAlias] = !$value ? $value : $this->toArray([$key => $value], $memory[$nextAlias]);
        }

        return $memory;
    }
}
