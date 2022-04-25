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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\State\StatisticsProvider;

#[Get(
    uriTemplate: '/statistics/cars/{identifier}.{_format}',
    output: false,
    status: 404
)]
#[GetCollection(
    uriTemplate: '/statistics/cars.{_format}',
    provider: StatisticsProvider::class
)]
class CarStatistics
{
    #[ApiProperty(identifier: true)]
    public string $identifier;
    public bool $sold;
    #[ApiProperty(types: ['https://schema.org/color'])]
    public ?string $color = null;

    #[ApiProperty(
        types: ['http://schema.org/MonetaryAmount']
    )]
    private MonetaryAmount $totalPrice;

    public function setTotalPrice(float $value = 0.0): self
    {
        $this->totalPrice = new MonetaryAmount($value);

        return $this;
    }

    public function getTotalPrice(): MonetaryAmount
    {
        return $this->totalPrice;
    }
}
