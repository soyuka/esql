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
use App\Repository\ModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource
 * @ORM\Entity(repositoryClass=ModelRepository::class)
 */
final class Model
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\OneToMany(targetEntity=Car::class, mappedBy="model", orphanRemoval=true)
     */
    private Collection $cars;

    public function __construct()
    {
        $this->cars = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCars(): array
    {
        return $this->cars->getValues();
    }

    // public function addCar(Car $car): self
    // {
    //     if (!$this->cars->contains($car)) {
    //         $this->cars[] = $car;
    //         $car->setModel($this);
    //     }
    //
    //     return $this;
    // }
    //
    // public function removeCar(Car $car): self
    // {
    //     if ($this->cars->removeElement($car)) {
    //         // set the owning side to null (unless already changed)
    //         if ($car->getModel() === $this) {
    //             $car->setModel(null);
    //         }
    //     }
    //
    //     return $this;
    // }
}
