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
    /** @var ESQLInterface[] */
    private array $closures = [];
    private static array $aliases = [];
    private static array $countAliases = [];
    private static array $classAliases = [];

    /** @var mixed */
    protected $metadata = null;
    protected string $alias = '';
    protected string $table = '';
    public string $class = '';

    abstract public function table(): string;

    abstract public function alias(): string;

    abstract public function columns(?array $fields = null, string $glue = ', '): string;

    abstract public function column(string $fieldName): ?string;

    abstract public function identifier(): string;

    abstract public function join(string $relationClass): string;

    abstract public function predicates(?array $fields = null, string $glue = ', '): string;

    abstract public function toSQLValue(string $fieldName, $value);

    abstract public function map(array $data);

    public function parameters(array $bindings): string
    {
        return ':'.implode(', :', array_keys($bindings));
    }

    /**
     * Get class metadata.
     *
     * @return mixed
     */
    abstract protected function getClassMetadata(string $class);

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
     */
    public function __invoke($objectOrClass): ESQLInterface
    {
        $class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass);
        if (isset($this->closures[$class])) {
            return $this->closures[$class];
        }

        $that = clone $this;
        $that->class = $class;
        $that->alias = $that->getAlias($class);
        $that->metadata = $this->getClassMetadata($class);
        $that->table = "{$that->metadata->getTableName()} {$that->alias}";

        return $this->closures[$class] = $that;
    }
}
