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

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\Dto;

use ApiPlatform\Core\Annotation\ApiProperty;

class CarStatistics
{
    #[ApiProperty(identifier: true)]
    public string $identifier;
    public bool $sold;
    public ?string $color = null;
    public float $totalPrice = 0.0;
}
