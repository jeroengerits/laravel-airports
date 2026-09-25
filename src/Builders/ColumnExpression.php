<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Builders;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

/** @internal SQL templates with grammar-quoted identifiers; values use query bindings. */
final class ColumnExpression implements Expression
{
    /**
     * @param  literal-string  $template
     * @param  list<string>  $columns
     */
    public function __construct(private string $template, private array $columns) {}

    public function getValue(Grammar $grammar): string
    {
        return sprintf($this->template, ...array_map($grammar->wrap(...), $this->columns));
    }
}
