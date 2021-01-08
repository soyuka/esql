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

        return $metadata->getTableName();
    }

    public function columns($objectOrClass, string $glue = ', ', ?array $fields = null): string
    {
        $metadata = $this->getClassMetadata($objectOrClass);
        $fields = $fields ? array_intersect_key($metadata->fieldMappings, array_flip($fields)) : $metadata->fieldMappings;

        return implode($glue, array_map(fn ($value) => $value['columnName'], $fields));
    }

    public function identifierPredicate($objectOrClass): string
    {
        $metadata = $this->getClassMetadata($objectOrClass);

        return $this->predicates($objectOrClass, ' AND ', $metadata->getIdentifierFieldNames());
    }

    public function predicates($objectOrClass, string $glue = ', ', ?array $fields = null): string
    {
        $metadata = $this->getClassMetadata($objectOrClass);
        $fields = $fields ? array_intersect_key($metadata->fieldMappings, array_flip($fields)) : $metadata->fieldMappings;
        $str = '';
        foreach ($fields as $fieldName => $field) {
            $str .= $str ? $glue : '';
            $str .= $field['columnName'].' = :'.$fieldName;
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
