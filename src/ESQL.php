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

abstract class ESQL implements ESQLInterface
{
    private ?array $closures = null;

    /**
     * Retrieves the Table name for the given resource.
     *
     * @param object|string $objectOrClass
     */
    abstract public function table($objectOrClass): string;

    /**
     * Retrieves columns for a given resource.
     *
     * @param object|string $objectOrClass
     */
    abstract public function columns($objectOrClass, string $glue = ', ', ?array $fields = null): string;

    /**
     * Retrieves identifiers predicate, for example id = :id.
     *
     * @param object|string $objectOrClass
     */
    abstract public function identifierPredicate($objectOrClass): string;

    /**
     * Retrieves identifiers predicate, for example foo = :foo.
     *
     * @param object|string $objectOrClass
     */
    abstract public function predicates($objectOrClass, string $glue = ', ', ?array $fields = null): string;

    /**
     * Retrieves a list of binded parameters.
     */
    public function parameters(array $bindings): string
    {
        return ':'.implode(', :', array_keys($bindings));
    }

    public function __invoke(): array
    {
        if ($this->closures) {
            return $this->closures;
        }

        return $this->closures = [
            'table' => $this->makeClosure('table'),
            'columns' => $this->makeClosure('columns'),
            'parameters' => $this->makeClosure('parameters'),
            'identifierPredicate' => $this->makeClosure('identifierPredicate'),
            'predicates' => $this->makeClosure('predicates'),
        ];
    }

    private function makeClosure(string $method): \Closure
    {
        return fn (): string => (string) \call_user_func_array([$this, $method], \func_get_args());
    }
}
