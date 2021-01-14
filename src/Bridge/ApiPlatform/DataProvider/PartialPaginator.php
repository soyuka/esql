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

namespace Soyuka\ESQL\Bridge\ApiPlatform\DataProvider;

use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;

final class PartialPaginator implements PartialPaginatorInterface, \IteratorAggregate
{
    private \Iterator $iterator;
    private float $currentPage;
    private float $itemsPerPage;

    public function __construct(array $data, float $currentPage, float $itemsPerPage)
    {
        $this->iterator = (new \ArrayObject($data))->getIterator();
        $this->currentPage = $currentPage;
        $this->itemsPerPage = $itemsPerPage;
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

    public function count(): int
    {
        return iterator_count($this->iterator);
    }
}
