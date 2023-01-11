<?php

namespace Getpastthemonkey\DbLink\exceptions;

final class ValidationException extends DbLinkException
{
    public readonly ?array $children;

    public function __construct(string $message, ?array $children = null)
    {
        parent::__construct($message);
        $this->children = $children;
    }
}
