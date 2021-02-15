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

use LogicException;
use Soyuka\ESQL\Exception\InvalidArgumentException;

abstract class ESQL implements ESQLInterface
{
    /** @var mixed */
    protected $metadata = null;
    public ?ESQLAlias $alias = null;
    protected string $table = '';
    /** @var class-string */
    protected ?string $class = null;
    /** @var class-string */
    protected ?string $mapTo = null;

    abstract public function table(): string;

    abstract public function alias(): string;

    abstract public function columns(?array $fields = null, int $output = ESQLInterface::AS_STRING);

    abstract public function column(string $fieldName): ?string;

    abstract public function identifier(): string;

    abstract public function join(string $relationClass): string;

    abstract public function predicates(?array $fields = null, int $output = ESQLInterface::AS_STRING);

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

    public function getAlias(): ESQLAliasInterface
    {
        if (null === $this->alias) {
            throw new LogicException('Alias not instantiated.');
        }

        return $this->alias;
    }

    public function __invoke($objectOrClass, ?string $mapTo = null): ESQLInterface
    {
        /** @var class-string */
        $class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass);
        $that = clone $this;

        if ($this->class && $this->alias) {
            $relationAlias = new ESQLAlias($this->getRelationAlias($this->class, $class), $this->alias);
            $this->alias->add($relationAlias);
            $that->alias = $relationAlias;
        } else {
            $that->alias = new ESQLAlias((new \ReflectionClass($class))->getShortName());
        }

        $that->class = $class;
        /** @var class-string */
        $that->mapTo = $mapTo;
        $that->metadata = $this->getClassMetadata($class);
        $schema = $that->metadata->getSchemaName() ? $that->metadata->getSchemaName().'.' : '';
        $that->table = "{$schema}{$that->metadata->getTableName()} {$that->alias}";

        return $that;
    }

    /**
     * @param class-string $class
     */
    private function getRelationAlias(string $class, string $relationClass): string
    {
        $refl = new \ReflectionClass($class);
        foreach ($refl->getProperties() as $prop) {
            $type = $prop->getType();
            if ($type instanceof \ReflectionNamedType && $type->getName() === $relationClass) {
                return $prop->getName();
            }
        }

        throw new InvalidArgumentException(sprintf('%s has no relation with %s.', $class, $relationClass));
    }
}
