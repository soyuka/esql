<?php

namespace Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * @ApiResource(iri="http://schema.org/Category")
 * @ORM\Entity()
 */
class Category
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    public string $name;

    /**
     * @ORM\Column(type="string", length=10)
     * @ORM\Id
     * @ApiProperty(iri="http://schema.org/identifier", identifier=true)
     */
    public string $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/identifier", identifier=true)
     */
    public string $category = '';

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     */
    private Collection $children;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="identifier")
     */
    public Category $parent;

    public function __construct() {
        $this->children = new ArrayCollection();
    }

    public function getChildren(): array
    {
        return $this->children->getValues();
    }

    public function setChildren(array $children): void
    {
        foreach($children as $child) {
            $child->parent = $this;
        }

        $this->children = new ArrayCollection($children);
    }
}
