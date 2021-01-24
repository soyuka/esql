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
use Soyuka\ESQL\ESQL as Base;
use Soyuka\ESQL\Exception\RuntimeException;

final class ESQL extends Base
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function table(): string
    {
        $metadata = $this->getClassMetadata($this->class);
        $alias = $this->getAlias($this->class);

        return "{$metadata->getTableName()} $alias";
    }

    public function columns(?array $fields = null, string $glue = ', '): string
    {
        $alias = $this->getAlias($this->class);
        $columns = [];
        foreach ($this->metadata->fieldMappings as $fieldName => $fieldMapping) {
            if ($fields && !\in_array($fieldName, $fields, true)) {
                continue;
            }

            $columns[] = "$alias.{$fieldMapping['columnName']} as {$alias}_{$fieldName}";
        }

        foreach ($this->metadata->getAssociationMappings() as $fieldName => $association) {
            if (!isset($association['joinColumns']) || $association['sourceEntity'] !== $this->class || ($fields && !\in_array($fieldName, $fields, true))) {
                continue;
            }

            foreach ($association['joinColumns'] as $i => $joinColumn) {
                $columns[] = "$alias.{$joinColumn['name']} as {$alias}_{$joinColumn['name']}";
            }
        }

        return implode($glue, $columns);
    }

    public function column(string $fieldName): ?string
    {
        $fieldMapping = $this->metadata->fieldMappings[$fieldName] ?? null;
        if (!$fieldMapping) {
            return null;
        }

        return "{$this->getAlias($this->class)}.{$fieldMapping['columnName']}";
    }

    public function identifierPredicate(): string
    {
        return $this->predicates($this->metadata->getIdentifierFieldNames(), ' AND ');
    }

    public function joinPredicate(string $relationClass): string
    {
        $alias = $this->getAlias($this->class);
        $relationMetadata = $this->getClassMetadata($relationClass);
        $relationAlias = $this->getAlias($relationClass);

        foreach ($this->metadata->getAssociationMappings() as $association) {
            if ($association['targetEntity'] === $relationMetadata->name) {
                $str = '';
                foreach ($association['joinColumns'] as $i => $joinColumn) {
                    $str .= 0 === $i ? '' : ' AND ';
                    $str .= "{$relationAlias}.{$joinColumn['referencedColumnName']}";
                    $str .= " = {$alias}.{$joinColumn['name']}";
                }

                return $str;
            }
        }

        throw new RuntimeException(sprintf('Relation between %s and %s was not found.', $this->metadata->name, $relationMetadata->name));
    }

    public function predicates(?array $fields = null, string $glue = ', '): string
    {
        $alias = $this->getAlias($this->class);
        $fields = $fields ? array_intersect_key($this->metadata->fieldMappings, array_flip($fields)) : $this->metadata->fieldMappings;
        $str = '';
        foreach ($fields as $fieldName => $field) {
            $str .= $str ? $glue : '';
            $str .= "{$alias}.{$field['columnName']} = :{$fieldName}";
        }

        return $str;
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

    public function getClassMetadata(string $class)
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
}
