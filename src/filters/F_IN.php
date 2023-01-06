<?php

namespace Getpastthemonkey\DbLink\filters;

use LogicException;

final class F_IN implements Filter
{
    private string $column;
    private array $values;

    public function __construct(string $column, array ...$values)
    {
        // Sanity check -> Must have at least one value
        if (count($values) == 0) {
            throw new LogicException("\"IN\"-Filter has no values. You must set at least one value.");
        }

        $this->column = $column;
        $this->values = $values;
    }

    public function get_where_clause(): string
    {
        $question_marks = array_fill(0, count($this->values), "?");
        $imploded = implode(", ", $question_marks);
        return "$this->column IN ($imploded)";
    }

    public function get_parameters(): array
    {
        return $this->values;
    }
}