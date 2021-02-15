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

namespace Soyuka\ESQL;

interface ESQLAliasInterface
{
    public function __construct(string $alias, ?self $parent = null);

    /**
     * Retrieves a key metadata having:.
     *
     * [$key, $alias, $this->getAliasedTo($relation), substr($key, $relationPos + 1)];
     * The key, the alias it belongs to, the relation alias if it exists, the key of the relation
     *
     * @return array{string, ?string, ?string, ?string}
     */
    public function metadata(string $key): array;

    /**
     * Adds an aliased property to this alias.
     */
    public function add(self $alias): self;

    /**
     * Get the alias for this property, looks into added aliases and calls `getAlias`.
     */
    public function getAliasedTo(string $property): string;

    /**
     * The origin alias name.
     */
    public function getAlias(): string;

    /**
     * The normalized alias, used to build the alias in __toString.
     */
    public function getNormalized(): string;

    /**
     * The alias used within queries.
     */
    public function __toString(): string;
}
