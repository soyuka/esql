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

use Jane\Component\AutoMapper\AutoMapperInterface;
use Soyuka\ESQL\ESQLMapper as Base;
use Soyuka\ESQL\ESQLMapperInterface;

final class ESQLMapper extends Base implements ESQLMapperInterface
{
    private AutoMapperInterface $automapper;

    public function __construct(AutoMapperInterface $automapper)
    {
        $this->automapper = $automapper;
    }

    public function map(array $data, string $class)
    {
        if (!\is_int(key($data))) {
            return $this->automapper->map($this->toArray($data), $class);
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->automapper->map($this->toArray($value), $class);
        }

        return $data;
    }
}
