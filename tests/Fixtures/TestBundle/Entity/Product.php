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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;

/**
 * @ORM\Entity
 */
#[ApiResource(iri: 'http://schema.org/Product', attributes: ['esql' => true])]
class Product
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ulid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UlidGenerator::class)
     */
    private Ulid $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[ApiProperty(iri: 'http://schema.org/name')]
    public string $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[ApiProperty(iri: 'http://schema.org/description')]
    public string $description;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @ORM\JoinColumn(name="category_id", referencedColumnName="identifier")
     *
     * @var Category
     */
    #[ApiProperty(readable: false)]
    public $categoryRelation; // this property is not typed on purpose

    #[ApiProperty(iri: 'http://schema.org/category')]
    private string $category = '';

    /**
     * @ORM\Column(type="string", length=14)
     */
    #[ApiProperty(iri: 'http://schema.org/gtin')]
    public string $gtin;

    public function setId(string $id): self
    {
        $this->id = Ulid::fromString($id);

        return $this;
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getCategory(): string
    {
        $category = $this->categoryRelation;
        $str = $category->name;
        while ($category = $category->parent) {
            $str = $category->name.' / '.$str;
        }

        return $str;
    }
}
