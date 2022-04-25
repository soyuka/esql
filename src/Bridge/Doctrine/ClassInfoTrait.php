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

/**
 * Retrieves information about a class.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait ClassInfoTrait
{
    /**
     * Get class name of the given object.
     *
     * @param object $object
     */
    private function getObjectClass($object): string
    {
        return $this->getRealClassName($object::class);
    }

    /**
     * Get the real class name of a class name that could be a proxy.
     */
    private function getRealClassName(string $className): string
    {
        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        if ((false === $positionCg = strrpos($className, '\\__CG__\\')) &&
            (false === $positionPm = strrpos($className, '\\__PM__\\'))) {
            return $className;
        }

        if (false !== $positionCg) {
            return substr($className, $positionCg + 8);
        }

        $className = ltrim($className, '\\');
        $pos = strrpos($className, '\\');
        if (false === $pos) {
            return $className;
        }

        return substr(
            $className,
            8 + $positionPm,
            $pos - ($positionPm + 8)
        );
    }
}
