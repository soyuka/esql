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
 * @psalm-type InvokeType = array{column: \Closure, columns: \Closure, identifier: \Closure, join: \Closure, parameters: \Closure, predicates: \Closure, table: string}[]
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

    abstract public function table(): string;

    abstract public function columns(?array $fields = null, string $glue = ', '): string;

    abstract public function column(string $fieldName): ?string;

    abstract public function identifierPredicate(): string;

    abstract public function joinPredicate(string $relationClass): string;

    abstract public function predicates(?array $fields = null, string $glue = ', '): string;

    abstract public function toSQLValue(string $fieldName, $value);

    public function parameters(array $bindings): string
    {
        return ':'.implode(', :', array_keys($bindings));
    }

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
            'alias' => $this->getAlias($class),
            'columns' => $this->makeClosure('columns', $that),
            'column' => $this->makeClosure('column', $that),
            'identifier' => $this->makeClosure('identifierPredicate', $that),
            'join' => $this->makeClosure('joinPredicate', $that),
            'predicates' => $this->makeClosure('predicates', $that),
            'toSQLValue' => $this->makeClosure('toSQLValue', $that),
            'supportsSQLClause' => $this->makeClosure('supportsSQLClause', $that),
            'relationFieldName' => $this->makeClosure('relationFieldName', $that),
            'parameters' => $this->makeClosure('parameters', $that),
        ];
    }

    private function makeClosure(string $method, self $that): \Closure
    {
        return fn (): string => (string) \call_user_func_array([$that, $method], \func_get_args());
    }
}
