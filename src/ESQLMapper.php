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
    abstract public function map(array $data, string $class, ESQLAliasInterface $a);

    // Todo: find an abstraction for aliases management
    protected function toArray(array $data, ESQLAliasInterface $a, array &$memory = []): array
    {
        foreach ($data as $key => $value) {
            [$key, $alias, $relation, $relationKey] = $a->metadata($key);
            if (!$relation) {
                $memory[$key] = $value;
                continue;
            }

            if (!isset($memory[$relation])) {
                $memory[$relation] = [];
                $memory[$relation] = !$value || !$key ? $value : $this->toArray([$relationKey ?? $key => $value], $a, $memory[$relation]);
            } elseif ($relationKey) {
                $memory[$relation][$relationKey] = $value;
            }
        }

        return $memory;
    }
}
