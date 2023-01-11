<?php

namespace Getpastthemonkey\DbLink\exceptions;

final class ObjectDoesNotExistException extends DbLinkException
{
    public readonly string $class;

    public function __construct(string $class)
    {
        parent::__construct("Query for single $class object returned no entry");
        $this->class = $class;
    }
}
