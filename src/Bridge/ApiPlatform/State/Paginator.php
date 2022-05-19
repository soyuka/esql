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

namespace Soyuka\ESQL\Bridge\ApiPlatform\State;

use ApiPlatform\State\Pagination\PaginatorInterface;

final class Paginator implements PaginatorInterface, \IteratorAggregate
{
    private readonly \Iterator $iterator;

    public function __construct(array $data, private readonly float $currentPage, private readonly float $itemsPerPage, private readonly float $totalItems = 0)
    {
        $this->iterator = (new \ArrayObject($data))->getIterator();
    }

    public function getCurrentPage(): float
    {
        return $this->currentPage;
    }

    public function getItemsPerPage(): float
    {
        return $this->itemsPerPage;
    }

    public function getIterator(): \Traversable
    {
        return $this->iterator;
    }

    public function getLastPage(): float
    {
        return ceil($this->totalItems / $this->itemsPerPage);
    }

    public function getTotalItems(): float
    {
        return $this->totalItems;
    }

    public function count(): int
    {
        return iterator_count($this->iterator);
    }
}
