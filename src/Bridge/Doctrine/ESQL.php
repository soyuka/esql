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

namespace Soyuka\ESQL\Bridge\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Soyuka\ESQL\ESQL as Base;
use Soyuka\ESQL\ESQLAlias;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\ESQLMapperInterface;
use Soyuka\ESQL\Exception\InvalidArgumentException;
use Soyuka\ESQL\Exception\RuntimeException;

final class ESQL extends Base
{
    public function __construct(private readonly ManagerRegistry $registry, private readonly ?ESQLMapperInterface $mapper = null)
    {
    }

    public function table(): string
    {
        return $this->table;
    }

    public function alias(): string
    {
        return (string) $this->alias;
    }

    public function columns(?array $fields = null, int $output = ESQLInterface::AS_STRING)
    {
        $columns = [];
        $onlyColumnNames = $output & ESQLInterface::WITHOUT_ALIASES;

        foreach ($this->metadata->fieldMappings as $fieldName => $fieldMapping) {
            if ($fields && !\in_array($fieldName, $fields, true)) {
                continue;
            }

            if ($output & ESQLInterface::IDENTIFIERS && !($fieldMapping['id'] ?? null)) {
                continue;
            }

            $columnName = "{$this->alias}.{$fieldMapping['columnName']}";
            $aliased = " as {$this->alias}_{$fieldName}";
            $columns[] = $onlyColumnNames ? $columnName : $columnName.$aliased;
        }

        if ($output & ESQLInterface::WITHOUT_JOIN_COLUMNS) {
            return $output & ESQLInterface::AS_ARRAY ? $columns : implode(', ', $columns);
        }

        foreach ($this->metadata->getAssociationMappings() as $fieldName => $association) {
            if (!isset($association['joinColumns']) || $association['sourceEntity'] !== $this->class || ($fields && !\in_array($fieldName, $fields, true))) {
                continue;
            }

            try {
                $relation = $this->__invoke($association['targetEntity']);
                $relationAlias = $relation->alias();
            } catch (InvalidArgumentException) {
                $relationAlias = (string) new ESQLAlias($fieldName, $this->alias);
            }

            foreach ($association['joinColumns'] as $i => $joinColumn) {
                $columnName = "$this->alias.{$joinColumn['name']}";
                $aliased = " as {$relationAlias}_{$joinColumn['referencedColumnName']}";
                $columns[] = $onlyColumnNames ? $columnName : $columnName.$aliased;
            }
        }

        return $output & ESQLInterface::AS_ARRAY ? $columns : implode(', ', $columns);
    }

    public function column(string $fieldName): ?string
    {
        $fieldMapping = $this->metadata->fieldMappings[$fieldName] ?? null;
        if (!$fieldMapping) {
            return null;
        }

        return "{$this->alias}.{$fieldMapping['columnName']}";
    }

    public function identifier(): string
    {
        return implode(' AND ', $this->predicates($this->metadata->getIdentifierFieldNames(), ESQLInterface::AS_ARRAY));
    }

    public function join(string $relationClass): string
    {
        $relationMetadata = $this->getClassMetadata($relationClass);
        foreach ($this->metadata->getAssociationMappings() as $association) {
            if ($association['targetEntity'] === $relationMetadata->name) {
                $str = '';
                try {
                    $relation = $this->__invoke($association['targetEntity']);
                    $relationAlias = $relation->alias();
                } catch (InvalidArgumentException) {
                    $relationAlias = (string) new ESQLAlias($association['fieldName'], $this->alias);
                }

                foreach ($association['joinColumns'] as $i => $joinColumn) {
                    $str .= 0 === $i ? '' : ' AND ';
                    $str .= "{$relationAlias}.{$joinColumn['referencedColumnName']}";
                    $str .= " = {$this->alias}.{$joinColumn['name']}";
                }

                return $str;
            }
        }

        throw new RuntimeException(sprintf('Relation between %s and %s was not found.', $this->metadata->name, $relationMetadata->name));
    }

    public function predicates(?array $fields = null, int $output = ESQLInterface::AS_STRING)
    {
        $fields = $fields ? array_intersect_key($this->metadata->fieldMappings, array_flip($fields)) : $this->metadata->fieldMappings;
        $result = [];
        foreach ($fields as $fieldName => $field) {
            $result[] = "{$this->alias}.{$field['columnName']} = :{$fieldName}";
        }

        return $output & ESQLInterface::AS_ARRAY ? $result : implode(', ', $result);
    }

    public function toSQLValue(string $fieldName, $value)
    {
        $fieldMapping = $this->metadata->fieldMappings[$fieldName] ?? null;
        if (!$fieldMapping) {
            return null;
        }

        $type = Type::getType($fieldMapping['type']);

        return $type->convertToDatabaseValue($value, $this->registry->getConnection()->getDatabasePlatform());
    }

    public function map(array $data)
    {
        if (!$this->mapper) {
            throw new LogicException('Mapper not available.');
        }

        if (!$this->class) {
            throw new LogicException('No class to map to.');
        }

        if (!$this->alias) {
            throw new LogicException('No alias to map from.');
        }

        return $this->mapper->map($data, $this->mapTo ?? $this->class, $this->alias);
    }

    protected function getClassMetadata(string $class)
    {
        $manager = $this->registry->getManagerForClass($class);
        if (!$manager) {
            throw new RuntimeException('No manager for class '.$class);
        }

        $classMetadata = $manager->getClassMetadata($class);
        if (!$classMetadata instanceof ClassMetadataInfo) {
            throw new RuntimeException('No class metadata for class '.$class);
        }

        return $classMetadata;
    }

    public function __invoke($objectOrClass, ?string $mapTo = null, ?string $aliasTo = null): ESQLInterface
    {
        try {
            return parent::__invoke($objectOrClass, $mapTo, $aliasTo);
        } catch (InvalidArgumentException) {
            /** @var class-string */
            $class = \is_string($objectOrClass) ? $objectOrClass : $objectOrClass::class;
            $that = clone $this;

            if ($this->class && $this->alias) {
                $relationAlias = new ESQLAlias($this->getRelationAlias($this->mapTo ?? $this->class, $class), $this->alias);
                $this->alias->add($relationAlias);
                $that->alias = $relationAlias;
            } else {
                /** @var ?class-string $mapTo */
                $that->alias = new ESQLAlias($aliasTo ?? (new \ReflectionClass($mapTo ?? $class))->getShortName());
            }

            $that->class = $class;
            /** @var class-string */
            $that->mapTo = $mapTo;
            $that->metadata = $this->getClassMetadata($class);
            $schema = $that->metadata->getSchemaName() ? $that->metadata->getSchemaName().'.' : '';
            $that->table = "{$schema}{$that->metadata->getTableName()} {$that->alias}";

            return $that;
        }
    }

    private function getRelationAlias(string $class, string $relationClass): string
    {
        try {
            $metadata = $this->getClassMetadata($class);
            $relationMetadata = $this->getClassMetadata($relationClass);
        } catch (RuntimeException) {
            throw new InvalidArgumentException(sprintf('%s has no relation with %s.', $class, $relationClass));
        }

        foreach ($metadata->getAssociationMappings() as $association) {
            if ($this->alias && $this->alias->hasAlias($association['fieldName'])) {
                continue;
            }

            if ($association['targetEntity'] === $relationMetadata->name) {
                return $association['fieldName'];
            }
        }

        throw new InvalidArgumentException(sprintf('%s has no relation with %s.', $class, $relationClass));
    }
}
