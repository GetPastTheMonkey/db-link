<?php

namespace Getpastthemonkey\DbLink\filters;

abstract class BinaryOperationFilter implements Filter
{
    private readonly string|int $operand_left;
    private readonly string|int $operand_right;
    private readonly string $operation;
    private readonly bool $left_is_value;
    private readonly bool $right_is_value;

    public function __construct(string|int $operand_left, string|int $operand_right, string $operation, bool $left_is_value = false, bool $right_is_value = true)
    {
        $this->operand_left = $operand_left;
        $this->operand_right = $operand_right;
        $this->operation = $operation;

        $this->left_is_value = $left_is_value;
        $this->right_is_value = $right_is_value;
    }

    public function get_where_clause(): string
    {
        $ol = $this->left_is_value ? "?" : $this->operand_left;
        $or = $this->right_is_value ? "?" : $this->operand_right;
        $op = $this->operation;

        return "$ol $op $or";
    }

    public function get_parameters(): array
    {
        $arr = array();
        if ($this->left_is_value) $arr[] = $this->operand_left;
        if ($this->right_is_value) $arr[] = $this->operand_right;
        return $arr;
    }
}
