<?php

namespace Getpastthemonkey\DbLink\filters;

final class F_NOT implements Filter
{
    private readonly Filter $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function get_where_clause(): string
    {
        $f1_query = $this->filter->get_where_clause();
        return "NOT ($f1_query)";
    }

    public function get_parameters(): array
    {
        return $this->filter->get_parameters();
    }
}
