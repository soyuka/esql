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

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Soyuka\ESQL\ESQL as Base;

final class ESQL extends Base
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function table($objectOrClass): string
    {
        $metadata = $this->getClassMetadata($objectOrClass);
        $alias = $this->getAlias($objectOrClass);

        return $metadata->getTableName().' '.$alias;
    }

    public function columns($objectOrClass, ?array $fields = null, string $glue = ', '): string
    {
        $metadata = $this->getClassMetadata($objectOrClass);
        $fields = $fields ? array_intersect_key($metadata->fieldMappings, array_flip($fields)) : $metadata->fieldMappings;
        $alias = $this->getAlias($objectOrClass);

        return implode($glue, array_map(fn ($value) => $alias.'.'.$value['columnName'].' as '.$alias.'_'.$value['columnName'], $fields));
    }

    public function column($objectOrClass, string $fieldName): ?string
    {
        $metadata = $this->getClassMetadata($objectOrClass);
        $fieldMapping = $metadata->fieldMappings[$fieldName] ?? null;
        if (!$fieldMapping) {
            return null;
        }

        return $this->getAlias($objectOrClass).'.'.$fieldMapping['columnName'];
    }

    public function identifierPredicate($objectOrClass): string
    {
        $metadata = $this->getClassMetadata($objectOrClass);

        return $this->predicates($objectOrClass, $metadata->getIdentifierFieldNames(), ' AND ');
    }

    public function joinPredicate($objectOrClass, $relationObjectOrClass): string
    {
        $metadata = $this->getClassMetadata($objectOrClass);
        $alias = $this->getAlias($objectOrClass);
        $relationMetadata = $this->getClassMetadata($relationObjectOrClass);
        $relationAlias = $this->getAlias($relationObjectOrClass);

        foreach ($metadata->getAssociationMappings() as $association) {
            if ($association['targetEntity'] === $relationMetadata->name) {
                $str = '';
                foreach ($association['joinColumns'] as $i => $joinColumn) {
                    $str .= 0 === $i ? '' : ' AND ';
                    $str .= $relationAlias.'.'.$joinColumn['referencedColumnName'];
                    $str .= ' = '.$alias.'.'.$joinColumn['name'];
                }

                return $str;
            }
        }

        throw new LogicException(sprintf('Relation between %s and %s was not found.', $metadata->name, $relationMetadata->name));
    }

    public function predicates($objectOrClass, ?array $fields = null, string $glue = ', '): string
    {
        $metadata = $this->getClassMetadata($objectOrClass);
        $alias = $this->getAlias($objectOrClass);
        $fields = $fields ? array_intersect_key($metadata->fieldMappings, array_flip($fields)) : $metadata->fieldMappings;
        $str = '';
        foreach ($fields as $fieldName => $field) {
            $str .= $str ? $glue : '';
            $str .= $alias.'.'.$field['columnName'].' = :'.$fieldName;
        }

        return $str;
    }

    /**
     * @param object|string $objectOrClass
     */
    private function getClassMetadata($objectOrClass): ClassMetadataInfo
    {
        $manager = $this->registry->getManagerForClass($class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass));
        if (!$manager) {
            throw new \RuntimeException('No manager for class '.$class);
        }
        $classMetadata = $manager->getClassMetadata($class);
        if (!$classMetadata instanceof ClassMetadataInfo) {
            throw new \RuntimeException('No class metadata for class '.$class);
        }

        return $classMetadata;
    }
}
