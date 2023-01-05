<?php

namespace Getpastthemonkey\DbLink\filters;

class F_BETWEEN implements Filter
{
    private string $column;
    private string|int $left_val;
    private string|int $right_val;

    public function __construct(string $column, string|int $left_val, string|int $right_val)
    {
        $this->column = $column;
        $this->left_val = $left_val;
        $this->right_val = $right_val;
    }

    public function get_where_clause(): string
    {
        return "$this->column BETWEEN ? AND ?";
    }

    public function get_parameters(): array
    {
        return array($this->left_val, $this->right_val);
    }
}
