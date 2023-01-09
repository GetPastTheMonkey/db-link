<?php

namespace Getpastthemonkey\DbLink\fields;

use Getpastthemonkey\DbLink\exceptions\ValidationException;

class CharField extends Field
{
    public readonly int $max_length;

    public function __construct(int $max_len = 255, mixed $default = null, bool $is_null_allowed = false, bool $is_primary_key = false)
    {
        parent::__construct($default, $is_null_allowed, $is_primary_key);
        $this->max_length = $max_len;
    }

    public function validate(mixed $value): void
    {
        parent::validate($value);

        if (!is_string($value)) {
            throw new ValidationException(static::class . " value is not a string");
        }

        $value_len = strlen($value);

        if ($value_len > $this->max_length) {
            throw new ValidationException(static::class . " value is too long. Length is " . $value_len . ". Max length is " . $this->max_length);
        }
    }
}
