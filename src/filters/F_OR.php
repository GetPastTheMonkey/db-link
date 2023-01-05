<?php

namespace Getpastthemonkey\DbLink\filters;

final class F_OR implements Filter
{
    private readonly Filter $f1;
    private readonly Filter $f2;

    public function __construct(Filter $f1, Filter $f2)
    {
        $this->f1 = $f1;
        $this->f2 = $f2;
    }

    public function get_where_clause(): string
    {
        $f1_query = $this->f1->get_where_clause();
        $f2_query = $this->f2->get_where_clause();
        return "($f1_query) OR ($f2_query)";
    }

    public function get_parameters(): array
    {
        return array_merge($this->f1->get_parameters(), $this->f2->get_parameters());
    }
}
