<?php

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
