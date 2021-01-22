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
     */
    public function table(): string;

    /**
     * Retrieves columns for a given resource.
     */
    public function columns(?array $fields = null, string $glue = ', '): string;

    /**
     * Retrieves a column for a given resource.
     */
    public function column(string $fieldName): ?string;

    /**
     * Retrieves identifiers predicate, for example id = :id.
     */
    public function identifierPredicate(): string;

    /**
     * Retrieves join predicate, for example car.model_id = model.id.
     */
    public function joinPredicate(string $relationClass): string;

    /**
     * Relation field name.
     */
    public function relationFieldName(string $relationClass): string;

    /**
     * Retrieves identifiers predicate, for example foo = :foo.
     */
    public function predicates(?array $fields = null, string $glue = ', '): string;

    /**
     * Normalize this sql value according to the field type.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function toSQLValue(string $fieldName, $value);

    /**
     * Whether the current driver supports an SQL clause.
     */
    public function supportsSQLClause(string $sqlClause): bool;

    /**
     * Retrieves a list of binded parameters.
     * more a helper for persistence not used.
     */
    public function parameters(array $bindings): string;

    /**
     * Get closures to ease HEREDOC calls.
     *
     * @param object|string $objectOrClass
     */
    public function __invoke($objectOrClass): array;

    /**
     * Get class metadata.
     *
     * @return mixed
     */
    public function getClassMetadata(string $class);

    /**
     * Get the class alias.
     *
     * @param object|string $objectOrClass
     */
    public static function getAlias($objectOrClass): string;

    /**
     * Get the class matching a given alias.
     */
    public static function getClass(string $alias): string;
}
