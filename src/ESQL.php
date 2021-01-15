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

/**
 * @psalm-type InvokeType = array{column: \Closure, columns: \Closure, identifierPredicate: \Closure, joinPredicate: \Closure, parameters: \Closure, predicates: \Closure, table: string}[]
 */
abstract class ESQL implements ESQLInterface
{
    /** @psalm-var InvokeType[] */
    private array $closures = [];
    private static array $aliases = [];
    private static array $countAliases = [];
    private static array $classAliases = [];

    /** @var mixed */
    protected $metadata = null;
    protected string $class = '';

    /**
     * Retrieves the Table name for the given resource.
     */
    abstract public function table(): string;

    /**
     * Retrieves columns for a given resource.
     */
    abstract public function columns(?array $fields = null, string $glue = ', '): string;

    abstract public function column(string $fieldName): ?string;

    /**
     * Retrieves identifiers predicate, for example id = :id.
     */
    abstract public function identifierPredicate(): string;

    /**
     * Retrieves join predicate, for example car.model_id = model.id.
     */
    abstract public function joinPredicate(string $relationClass): string;

    /**
     * Retrieves identifiers predicate, for example foo = :foo.
     */
    abstract public function predicates(?array $fields = null, string $glue = ', '): string;

    /**
     * Retrieves a list of binded parameters.
     */
    public function parameters(array $bindings): string
    {
        return ':'.implode(', :', array_keys($bindings));
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

    /**
     * @param object|string $objectOrClass
     *
     * @psalm-return InvokeType
     */
    public function __invoke($objectOrClass): array
    {
        $class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass);
        if (isset($this->closures[$class])) {
            return $this->closures[$class];
        }

        $that = clone $this;
        $that->class = $class;
        $that->metadata = $this->getClassMetadata($class);

        return $this->closures[$class] = [
            'table' => $that->table(),
            'columns' => $this->makeClosure('columns', $that, $class),
            'column' => $this->makeClosure('column', $that, $class),
            'identifierPredicate' => $this->makeClosure('identifierPredicate', $that, $class),
            'joinPredicate' => $this->makeClosure('joinPredicate', $that, $class),
            'predicates' => $this->makeClosure('predicates', $that, $class),
            'parameters' => $this->makeClosure('parameters', $that, $class),
        ];
    }

    private function makeClosure(string $method, self $that, string $class): \Closure
    {
        return fn (): string => (string) \call_user_func_array([$that, $method], \func_get_args());
    }
}
