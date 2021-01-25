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

use Doctrine\Persistence\ManagerRegistry;
use JMS\Parser\AbstractParser;
use JMS\Parser\SimpleLexer;
use Soyuka\ESQL\ESQLInterface;
use Soyuka\ESQL\Exception\InvalidArgumentException;
use Soyuka\ESQL\Exception\RuntimeException;

/**
 * Implements parsing referenced at https://postgrest.org/en/v7.0.0/api.html#horizontal-filtering-rows
 * Example: and=(price.gt.1000,sold.is.false,or(name.not.eq.caddy,sold.is.true)).
 */
final class FilterParser extends AbstractParser implements FilterParserInterface
{
    public const T_UNKNOWN = 0;
    public const T_AND = 1;
    public const T_OR = 2;
    public const T_OPEN = 3;
    public const T_CLOSE = 4;
    public const T_OPERATOR = 5;
    public const T_VALUE = 6;
    public const T_COLON = 7;
    public const T_WORD = 8;

    private ESQLInterface $esql;
    private ManagerRegistry $registry;
    private array $parameterNames = [];

    public function __construct(ESQLInterface $esql, ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->esql = $esql;
        parent::__construct(new SimpleLexer('/
            (and)|(or)
            | (\() | (\))
            | (gte|gt|lte|lt|neq|eq|ilike|like|in|is|not\.neq|not\.eq|not\.gte|not\.gt|not\.lte|not\.lt|not\.like|not\.ilike|not\.in|not\.is)
            | \.
            | ([a-zA-Z0-9*]+[^().,]*)
            | (,)
        /x',
            [
                self::T_UNKNOWN => 'T_UNKNOWN',
                self::T_AND => 'T_AND',
                self::T_OR => 'T_OR',
                self::T_OPEN => 'T_OPEN',
                self::T_CLOSE => 'T_CLOSE',
                self::T_OPERATOR => 'T_OPERATOR',
                self::T_VALUE => 'T_VALUE',
                self::T_COLON => 'T_COLON',
                self::T_WORD => 'T_WORD',
            ],
            [$this, 'determineTypeAndValue']
        ));
    }

    public function parse($str, $context = null)
    {
        if (!$context) {
            throw new InvalidArgumentException('Parsing a filter without a class as second argument is not possible.');
        }

        $this->context = $context;
        $this->lexer->setInput($str);
        $driverName = $this->registry->getConnection()->getDriver()->getName();
        $esql = $this->esql->__invoke($context);

        $result = '';
        $parameters = [];
        $openingParenthesis = -1;
        $closingParenthesis = -1;
        $operator = '';

        while ($this->lexer->isNextAny([self::T_COLON, self::T_AND, self::T_OR, self::T_OPEN, self::T_CLOSE])) {
            if ($this->lexer->isNext(self::T_AND)) {
                $operator = ' AND ';
                $result .= $result ? ' AND ' : '';
                $this->lexer->moveNext();
            } elseif ($this->lexer->isNext(self::T_OR)) {
                $operator = ' OR ';
                $result .= $result ? ' OR ' : '';
                $this->lexer->moveNext();
            } elseif ($this->lexer->isNext(self::T_OPEN)) {
                $this->lexer->moveNext();
                $result .= ++$openingParenthesis < 1 ? '' : '(';
            } elseif ($this->lexer->isNext(self::T_CLOSE)) {
                $result .= ++$closingParenthesis < 1 ? '' : ')';
                $this->lexer->moveNext();
            }

            if ($this->lexer->isNextAny([self::T_WORD, self::T_COLON])) {
                if ($this->lexer->isNext(self::T_COLON)) {
                    $this->lexer->moveNext();
                    if ($this->lexer->isNext(self::T_WORD)) {
                        $result .= $operator;
                    }
                }

                if ($this->lexer->isNext(self::T_WORD)) {
                    [$parameterName, $columnValue] = $this->match(self::T_WORD);
                    $uniqueParameterName = $this->uniqueParameterName($parameterName);
                    $sqlOperator = $this->match(self::T_OPERATOR);

                    // The value is an array
                    if ($this->lexer->isNext(self::T_OPEN)) {
                        $result .= "$columnValue $sqlOperator (";
                        $this->lexer->moveNext();
                        while (!$this->lexer->isNext(self::T_CLOSE)) {
                            if ($this->lexer->isNext(self::T_COLON)) {
                                $result .= ',';
                                $this->lexer->moveNext();
                            } elseif ($this->lexer->isNext(self::T_VALUE)) {
                                $result .= ":$uniqueParameterName";
                                $parameters[$uniqueParameterName] = $this->match(self::T_VALUE);
                                $uniqueParameterName = $this->uniqueParameterName($parameterName);
                            } else {
                                throw new InvalidArgumentException('Argument malformed');
                            }
                        }

                        $result .= ')';
                        $this->lexer->moveNext();
                        continue;
                    }

                    $value = $this->match(self::T_VALUE);
                    if (!$this->supportsSQLClause($sqlOperator, $driverName)) {
                        throw new InvalidArgumentException("The operator '$sqlOperator' is not supported.");
                    }

                    if (0 === strpos($sqlOperator, 'IS')) {
                        if (!\in_array($value, [true, false, null], true)) {
                            throw new InvalidArgumentException('IS only works with true, false or null.');
                        }

                        $result .= "$columnValue $sqlOperator ".(true === $value ? 'TRUE' : (false === $value ? 'FALSE' : 'NULL'));
                    } else {
                        $result .= "$columnValue $sqlOperator :$uniqueParameterName";
                        if (false !== strpos($sqlOperator, 'LIKE')) {
                            $value = str_replace('*', '%', $value);
                        }

                        $parameters[$uniqueParameterName] = $esql->toSQLValue($parameterName, $value);
                    }
                }
            }
        }

        if (null !== $this->lexer->next) {
            $this->syntaxError('end of input');
        }

        return [$result, $parameters];
    }

    /**
     * Not implemented, we override parse directly.
     */
    protected function parseInternal(): void
    {
        throw new RuntimeException('Can not call parseInternal');
    }

    private function operatorToSQLCondition(string $condition): string
    {
        $negation = false;
        if (0 === strpos($condition, 'not.')) {
            $condition = substr($condition, 4);
            $negation = true;
        }

        switch ($condition) {
            case 'eq':
                return $negation ? '!=' : '=';
            case 'gt':
                return $negation ? '<' : '>';
            case 'gte':
                return $negation ? '<=' : '>=';
            case 'lt':
                return $negation ? '>' : '<';
            case 'lte':
                return $negation ? '>=' : '<=';
            case 'neq':
                return $negation ? '=' : '!=';
            case 'ilike':
                return $negation ? 'NOT ILIKE' : 'ILIKE';
            case 'like':
                return $negation ? 'NOT LIKE' : 'LIKE';
            case 'is':
                return $negation ? 'IS NOT' : 'IS';
            case 'in':
                return $negation ? 'NOT IN' : 'IN';
        }

        throw new InvalidArgumentException($condition.' is not supported.');
    }

    private function uniqueParameterName(string $parameterName): string
    {
        if (isset($this->parameterNames[$parameterName])) {
            return $parameterName.'_'.(++$this->parameterNames[$parameterName]);
        }

        $this->parameterNames[$parameterName] = 1;

        return $parameterName.'_1';
    }

    /**
     * @param mixed $value
     */
    public function determineTypeAndValue($value): array
    {
        $esql = $this->esql->__invoke($this->context);
        if ('and' === $value) {
            return [self::T_AND, 'and'];
        }
        if ('or' === $value) {
            return [self::T_OR, 'or'];
        }
        if ('(' === $value) {
            return [self::T_OPEN, '('];
        }
        if (')' === $value) {
            return [self::T_CLOSE, ')'];
        }
        if (1 === preg_match('(eq|gt|gte|lte|lt|neq|like|ilike|in|is)', $value)) {
            return [self::T_OPERATOR, $this->operatorToSQLCondition($value)];
        }

        if (',' === $value) {
            return [self::T_COLON, $value];
        }

        if (\is_string($value) && ($col = $esql->column($value))) {
            return [self::T_WORD, [$value, $col]];
        }

        if (\is_string($value)) {
            $lower = strtolower($value);
            if ('true' === $lower) {
                $value = true;
            } elseif ('false' === $lower) {
                $value = false;
            } elseif ('null' === $lower) {
                $value = null;
            }

            return [self::T_VALUE, $value];
        }

        return [self::T_UNKNOWN, $value];
    }

    private function supportsSQLClause(string $sqlClause, string $driver): bool
    {
        switch ($driver) {
          case 'pdo_sqlite':
            return 'ILIKE' === $sqlClause || 'IS' === $sqlClause ? false : true;
      }

        return true;
    }
}
