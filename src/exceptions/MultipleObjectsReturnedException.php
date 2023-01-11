<?php

namespace Getpastthemonkey\DbLink\exceptions;

final class MultipleObjectsReturnedException extends DbLinkException
{
    public readonly string $class;
    public readonly int $count;

    public function __construct(string $class, int $count)
    {
        parent::__construct("Query for single $class object returned $count entries");
        $this->class = $class;
        $this->count = $count;
    }
}
