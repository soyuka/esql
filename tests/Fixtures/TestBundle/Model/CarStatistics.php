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

#[GetCollection(
    uriTemplate: '/statistics/cars.{_format}',
    provider: StatisticsProvider::class
)]
#[Get(uriTemplate: '/statistics/cars/{identifier}.{_format}', output: false)]
class CarStatistics
{
    #[ApiProperty(identifier: true)]
    public string $identifier;
    public bool $sold;
    #[ApiProperty(types: ['https://schema.org/color'])]
    public ?string $color = null;

    #[ApiProperty(
        types: ['http://schema.org/MonetaryAmount'],
        iri: false
    )]
    public MonetaryAmount $totalPrice;
}
