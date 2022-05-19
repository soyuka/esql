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

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\Model;

class MonetaryAmount
{
    public function __construct(public readonly float $value = 0.0, public readonly string $currency = 'EUR', public readonly float $minValue = 0.0)
    {
    }
}
