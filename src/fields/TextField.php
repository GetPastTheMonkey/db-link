<?php

namespace Getpastthemonkey\DbLink\fields;

class TextField extends Field
{
    public function __construct(mixed $default = null, bool $is_null_allowed = false)
    {
        parent::__construct($default, $is_null_allowed);
    }
}
