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
    public const AS_STRING = 1;
    public const AS_ARRAY = 2;
    public const WITHOUT_ALIASES = 4;
    public const WITHOUT_JOIN_COLUMNS = 8;
    public const IDENTIFIERS = 16;

    /**
     * Retrieves the table.
     */
    public function table(): string;

    /**
     * Retrieves the alias.
     */
    public function alias(): string;

    /**
     * @template TFlags as int-mask<ESQLInterface::AS_STRING, ESQLInterface::AS_ARRAY, ESQLInterface::WITHOUT_ALIASES, ESQLInterface::WITHOUT_JOIN_COLUMNS>
     *
     * @param TFlags $output
     *
     * @return ($output is ESQLInterface::AS_STRING ? string : array)
     */
    public function columns(?array $fields = null, int $output = self::AS_STRING);

    /**
     * Retrieves a column for a given resource.
     */
    public function column(string $fieldName): ?string;

    /**
     * Retrieves identifiers predicate, for example id = :id.
     */
    public function identifier(): string;

    /**
     * Retrieves join predicate, for example car.model_id = model.id.
     */
    public function join(string $relationClass): string;

    /**
     * @template TFlags as int-mask<ESQLInterface::AS_STRING, ESQLInterface::AS_ARRAY, ESQLInterface::WITHOUT_ALIASES, ESQLInterface::WITHOUT_JOIN_COLUMNS>
     *
     * @param TFlags $output
     *
     * @return ($output is ESQLInterface::AS_STRING ? string : array)
     */
    public function predicates(?array $fields = null, int $output = self::AS_STRING);

    /**
     * Normalize this sql value according to the field type.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function toSQLValue(string $fieldName, $value);

    /**
     * Map the array data to the class.
     *
     * If we had generics we'd type this to $this->class
     *
     * @return mixed
     */
    public function map(array $data);

    /**
     * Retrieves a list of binded parameters.
     * more a helper for persistence not used.
     */
    public function parameters(array $bindings): string;

    /**
     * Get closures to ease HEREDOC calls.
     *
     * @param object|class-string|string $objectOrClass
     * @param string|class-string|null   $mapTo
     */
    public function __invoke($objectOrClass, ?string $mapTo = null): self;

    // /**
    //  * Get the class alias.
    //  *
    //  * @param object|string $objectOrClass
    //  */
    // public function getAlias(): ?ESQLAlias;
    //     return $thos->alias;
    //
    // }
    //
    // /**
    //  * Get the class matching a given alias.
    //  */
    // public static function getClass(string $alias): string;
}
