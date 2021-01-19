<?php

namespace Soyuka\ESQL\Filter;

use InvalidArgumentException;
use JMS\Parser\AbstractParser;
use JMS\Parser\SimpleLexer;
use LogicException;
use Soyuka\ESQL\ESQLInterface;

/**
 * Implements parsing referenced at https://postgrest.org/en/v7.0.0/api.html#horizontal-filtering-rows
 * Example: and=(price.gt.1000,sold.is.false,or(name.not.eq.caddy,sold.is.true))
 */
final class FilterParser extends AbstractParser implements FilterParserInterface
{
    const T_UNKNOWN = 0;
    const T_AND = 1;
    const T_OR = 2;
    const T_OPEN = 3;
    const T_CLOSE = 4;
    const T_OPERATOR = 5;
    const T_VALUE = 6;
    const T_COLON = 7;
    const T_WORD = 8;

    private ESQLInterface $esql;
    private array $parameterNames = [];

    public function __construct(ESQLInterface $esql) 
    {
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
                self::T_WORD => 'T_WORD'
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
        ['toSQLValue' => $toSQLValue] = $this->esql->__invoke($context);

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
            } else if ($this->lexer->isNext(self::T_OR)) {
                $operator = ' OR ';
                $result .= $result ? ' OR ' : '';
                $this->lexer->moveNext();
            } else if ($this->lexer->isNext(self::T_OPEN)) {
                $this->lexer->moveNext();
                $result .= ++$openingParenthesis < 1 ? '' : '(';
            } else if ($this->lexer->isNext(self::T_CLOSE)) {
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
                    $result .= "$columnValue " . $sqlOperator . " :$uniqueParameterName";
                    $value = $this->match(self::T_VALUE);
                    if ($sqlOperator === 'LIKE' || $sqlOperator === 'ILIKE') {
                        $value = str_replace('*', '%', $value);
                    }
                    $parameters[$uniqueParameterName] = $toSQLValue($parameterName, $value);
                }
            }
        }

        if (null !== $this->lexer->next) {
            $this->syntaxError('end of input');
        }

        return [$result, $parameters];
    }

    /**
     * Not implemented, we override parse directly
     */
    protected function parseInternal() { throw new LogicException('Can not call parseInternal'); }

    private function operatorToSQLCondition(string $condition): string
    {
        $negation = false;
        if (0 === strpos($condition, 'not.'))
        {
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
            case 'like':
                return $negation ? 'NOT LIKE' : 'LIKE';
            case 'is':
                return $negation ? 'IS NOT' : 'IS';
            case 'in':
                return $negation ? 'NOT IN' : 'IN';
        }

        throw new InvalidArgumentException($condition . ' is not supported.');
    }

    private function uniqueParameterName(string $parameterName): string
    {
        if (isset($this->parameterNames[$parameterName])) {
            return $parameterName . '_' . (++$this->parameterNames[$parameterName]);
        }

        $this->parameterNames[$parameterName] = 1;
        return $parameterName . '_1';
    }

    /**
     * @param mixed $value
     */
    public function determineTypeAndValue($value): array
    {
        ['column' => $column] = $this->esql->__invoke($this->context);
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

        if (is_string($value) && ($col = $column($value))) {
            return [self::T_WORD, [$value, $col]];
        }

        if (is_string($value)) {
            if ($value === 'true') {
                $value = true;
            } else if ($value === 'false') {
                $value = false;
            }

            return [self::T_VALUE, $value];
        }

        return [self::T_UNKNOWN, $value];
    }
}
