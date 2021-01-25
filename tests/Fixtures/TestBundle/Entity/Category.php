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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(iri="http://schema.org/Category", attributes={"esql"=true})
 * @ORM\Entity
 */
class Category
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    public string $name;

    /**
     * @ORM\Column(type="string", length=30)
     * @ORM\Id
     * @ApiProperty(iri="http://schema.org/identifier", identifier=true)
     */
    public string $identifier;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     * @ApiProperty(readable=false)
     */
    private Collection $children;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="identifier")
     */
    public ?Category $parent = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getChildren(): array
    {
        return $this->children->getValues();
    }

    public function setChildren(array $children): void
    {
        foreach ($children as $child) {
            $child->parent = $this;
        }

        $this->children = new ArrayCollection($children);
    }
}
