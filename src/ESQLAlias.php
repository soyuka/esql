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

final class ESQLAlias implements ESQLAliasInterface, \Stringable
{
    private readonly string $normalized;
    /** @var ESQLAliasInterface[] */
    private array $aliases = [];

    public function __construct(private readonly string $alias, private readonly ?ESQLAliasInterface $parent = null)
    {
        $this->normalized = str_replace('_', '', strtolower($alias));
    }

    /**
     * @return array{string, ?string, ?string, ?string}
     */
    public function metadata(string $key): array
    {
        $aliasPos = strpos($key, '_');
        if (false === $aliasPos) {
            return [$key, null, null, null];
        }

        $alias = substr($key, 0, $aliasPos);
        $key = substr($key, $aliasPos + 1);
        $relationPos = strpos($key, '_');

        if (false === $relationPos) {
            return [$key, $alias, null, null];
        }

        $relation = substr($key, 0, $relationPos);

        return [$key, $alias, $this->getAliasedTo($relation), substr($key, $relationPos + 1)];
    }

    public function add(ESQLAliasInterface $alias): self
    {
        if (!isset($this->aliased[$alias->getNormalized()])) {
            $this->aliases[$alias->getNormalized()] = $alias;
        }

        return $this;
    }

    public function getAliasedTo(string $property): string
    {
        if (isset($this->aliases[$property])) {
            return $this->aliases[$property]->getAlias();
        }

        return $property;
    }

    public function hasAlias(string $property): bool
    {
        foreach ($this->aliases as $alias) {
            if ($alias->getAlias() === $property) {
                return true;
            }
        }

        return false;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getNormalized(): string
    {
        return $this->normalized;
    }

    public function __toString(): string
    {
        return $this->parent ? (string) $this->parent.'_'.$this->normalized : $this->normalized;
    }
}
