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

use ReflectionClass;

abstract class ESQL implements ESQLInterface
{
    private ?array $closures = null;
    private static array $aliases = [];
    private static array $countAliases = [];
    private static array $classAliases = [];

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
    abstract public function columns($objectOrClass, ?array $fields = null, string $glue = ', '): string;

    /**
     * Retrieves identifiers predicate, for example id = :id.
     *
     * @param object|string $objectOrClass
     */
    abstract public function identifierPredicate($objectOrClass): string;

    /**
     * Retrieves join predicate, for example car.model_id = model.id.
     *
     * @param object|string $objectOrClass
     * @param object|string $relationObjectOrClass
     */
    abstract public function joinPredicate($objectOrClass, $relationObjectOrClass): string;

    /**
     * Retrieves identifiers predicate, for example foo = :foo.
     *
     * @param object|string $objectOrClass
     */
    abstract public function predicates($objectOrClass, ?array $fields = null, string $glue = ', '): string;

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
            'joinPredicate' => $this->makeClosure('joinPredicate'),
            'predicates' => $this->makeClosure('predicates'),
        ];
    }

    /**
     * Retrieves the Table name for the given resource.
     *
     * @param object|string $objectOrClass
     */
    public static function getAlias($objectOrClass): string
    {
        /** @var class-string */
        $class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass);

        if (isset(self::$aliases[$class])) {
            return self::$aliases[$class];
        }

        $alias = strtolower((new ReflectionClass($class))->getShortName());
        self::$countAliases[$alias] = isset(self::$countAliases[$alias]) ? self::$countAliases[$alias] + 1 : 1;
        self::$aliases[$class] = 1 === self::$countAliases[$alias] ? $alias : $alias.self::$countAliases[$alias];
        self::$classAliases[self::$aliases[$class]] = $class;

        return self::$aliases[$class];
    }

    public static function getClass(string $alias): string
    {
        return self::$classAliases[$alias];
    }

    private function makeClosure(string $method): \Closure
    {
        return fn (): string => (string) \call_user_func_array([$this, $method], \func_get_args());
    }
}
