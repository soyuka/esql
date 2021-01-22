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

namespace Soyuka\ESQL\Bridge\Automapper;

use Jane\AutoMapper\AutoMapperInterface;
use Soyuka\ESQL\ESQL;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Exception\RuntimeException;

final class ESQLMapper implements ESQLMapperInterface
{
    private AutoMapperInterface $automapper;
    private ESQLInterface $esql;

    public function __construct(AutoMapperInterface $automapper, ESQLInterface $esql)
    {
        $this->automapper = $automapper;
        $this->esql = $esql;
    }

    public function map(array $data, string $resourceClass)
    {
        ['relationFieldName' => $relationFieldName] = $this->esql->__invoke($resourceClass);

        $memory = [];
        foreach ($data as $key => $value) {
            if (\is_int($key)) {
                $data[$key] = $this->map($value, $resourceClass);
                continue;
            }

            $aliasPos = strpos($key, '_');
            if (false === $aliasPos) {
                throw new RuntimeException('No alias found');
            }

            $alias = substr($key, 0, $aliasPos);
            $key = substr($key, $aliasPos + 1);
            $class = ESQL::getClass($alias);

            if (!isset($memory[$class])) {
                $memory[$class] = [];
            }

            $memory[$class][$key] = $value;
        }

        if (isset($memory[$resourceClass])) {
            $normalized = $memory[$resourceClass];
            unset($memory[$resourceClass]);
            foreach ($memory as $class => $value) {
                $normalized[$relationFieldName($class)] = $value;
                unset($memory[$class]);
            }

            return $this->automapper->map($normalized, $resourceClass);
        }

        return $data;
    }
}
