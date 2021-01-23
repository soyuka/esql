<?php

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * @ApiResource(iri="http://schema.org/Product")
 * @ORM\Entity()
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ulid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UlidGenerator::class)
     * @ApiProperty(identifier=false)
     */
    public Ulid $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(iri="http://schema.org/name")
     */
    public string $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(iri="http://schema.org/description")
     */
    public string $description;

    /**
     * @ORM\ManyToMany(targetEntity=Category::class)
     * @ORM\JoinTable(name="product_categories",
     *   joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="identifier")}
     * )
     */
    public Collection $categories;

    /**
     * @ApiProperty(iri="http://schema.org/category")
     */
    public string $category;

    /**
     * @ApiProperty(iri="https://schema.org/gtin")
     * @ORM\Column(type="string", length=14)
     */
    public string $gtin;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function getCategories(): array
    {
        return $this->categories->getValues();
    }

    public function setCategories(array $categories): self
    {
        $this->categories = new ArrayCollection($categories);
        return $this;
    }
}

