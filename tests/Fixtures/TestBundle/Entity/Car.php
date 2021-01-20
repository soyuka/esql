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

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(attributes={"order"={"name"="ASC", "id"="ASC"}, "esql"=true})
 * @ORM\Entity
 */
class Car
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public string $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public ?string $color = null;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    public int $price = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    public bool $sold = false;

    /**
     * @ORM\ManyToOne(targetEntity=Model::class, inversedBy="cars")
     * @ORM\JoinColumn(nullable=false)
     */
    public ?Model $model = null;
}
