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

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\Faker\Provider;

use Faker\Provider\Base;

/**
 * @psalm-suppress MissingDependency,
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CarProvider extends Base
{
    public static function carName(string $startWith): string
    {
        $name = $startWith;
        for ($i = 0; $i < static::numberBetween(5, 10); ++$i) {
            $name .= static::randomLetter();
        }

        return $name;
    }
}
