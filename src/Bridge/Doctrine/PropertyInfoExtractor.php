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

use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor as PropertyInfoDoctrineExtractor;

class PropertyInfoExtractor extends PropertyInfoDoctrineExtractor
{
    public function isWritable(string $class, string $property, array $context = [])
    {
        return null;
    }
}
