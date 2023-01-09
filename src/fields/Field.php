<?php

namespace Getpastthemonkey\DbLink\fields;

use Getpastthemonkey\DbLink\exceptions\ValidationException;

abstract class Field
{
    public readonly mixed $default;
    public readonly bool $is_null_allowed;
    public readonly bool $is_primary_key;

    public function __construct(mixed $default = null, bool $is_null_allowed = false, bool $is_primary_key = false)
    {
        $this->default = $default;
        $this->is_null_allowed = $is_null_allowed;
        $this->is_primary_key = $is_primary_key;
    }

    /**
     * @throws ValidationException
     */
    public function validate(mixed $value): void
    {
        if(!$this->is_null_allowed and is_null($value)) {
            throw new ValidationException(static::class . " value was NULL, but NULL is not allowed for this field");
        }
    }
}
