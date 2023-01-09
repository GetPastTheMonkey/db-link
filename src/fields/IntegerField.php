<?php

namespace Getpastthemonkey\DbLink\fields;

use Getpastthemonkey\DbLink\exceptions\ValidationException;

class IntegerField extends Field
{
    public readonly int $min;
    public readonly int $max;

    public function __construct(int $min = -2_147_483_648, int $max = 2_147_483_647, int $default = null, bool $is_null_allowed = false, bool $is_primary_key = false)
    {
        parent::__construct($default, $is_null_allowed, $is_primary_key);
        $this->min = $min;
        $this->max = $max;
    }

    public function validate(mixed $value): void
    {
        parent::validate($value);

        if (!is_numeric($value)) {
            throw new ValidationException($this->getShortName() . " value is not numeric");
        }

        $int_value = (int)$value;

        if (($int_value < $this->min) or ($int_value > $this->max)) {
            throw new ValidationException($this->getShortName() . " value of $int_value is not in the defined range [$this->min, $this->max]");
        }
    }
}
