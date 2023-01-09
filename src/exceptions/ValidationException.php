<?php

namespace Getpastthemonkey\DbLink\exceptions;

use Exception;

class ValidationException extends Exception
{
    public readonly array $children;

    public function __construct(string $message, array $children = null)
    {
        parent::__construct($message);
        $this->children = $children;
    }
}
