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

namespace Soyuka\ESQL\Filter;

interface FilterParserInterface
{
    /**
     * Parses the given input.
     *
     * @param string $str
     * @param string $context parsing context (allows to produce better error messages)
     *
     * @return mixed
     */
    public function parse($str, $context = null);
}
