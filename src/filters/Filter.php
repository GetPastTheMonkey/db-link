<?php

namespace Getpastthemonkey\DbLink\filters;

interface Filter
{
    public function get_where_clause(): string;

    public function get_parameters(): array;
}
