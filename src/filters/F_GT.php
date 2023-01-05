<?php

namespace Getpastthemonkey\DbLink\filters;

final class F_GT extends BinaryOperationFilter
{
    public function __construct(string $operand_left, string $operand_right, bool $left_is_value = false, bool $right_is_value = true)
    {
        parent::__construct($operand_left, $operand_right, ">", $left_is_value, $right_is_value);
    }
}
