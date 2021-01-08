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

interface ESQLInterface
{
    /**
     * Retrieves the Table name for the given resource.
     *
     * @param object|string $objectOrClass
     */
    public function table($objectOrClass): string;

    /**
     * Retrieves columns for a given resource.
     *
     * @param object|string $objectOrClass
     */
    public function columns($objectOrClass, string $glue = ', ', ?array $fields = null): string;

    /**
     * Retrieves identifiers predicate, for example id = :id.
     *
     * @param object|string $objectOrClass
     */
    public function identifierPredicate($objectOrClass): string;

    /**
     * Retrieves identifiers predicate, for example foo = :foo.
     *
     * @param object|string $objectOrClass
     */
    public function predicates($objectOrClass, string $glue = ', ', ?array $fields = null): string;

    /**
     * Retrieves a list of binded parameters.
     */
    public function parameters(array $bindings): string;

    /**
     * Get closures to ease HEREDOC calls.
     */
    public function __invoke(): array;
}
