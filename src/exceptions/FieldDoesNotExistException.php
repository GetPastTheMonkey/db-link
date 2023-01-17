<?php

namespace Getpastthemonkey\DbLink\exceptions;

use LogicException;
use ReflectionException;
use ReflectionMethod;

final class FieldDoesNotExistException extends DbLinkException
{
    public readonly string $field;
    public readonly string $class;

    public function __construct(string $field, string $class)
    {
        try {
            $reflection = new ReflectionMethod($class, "get_attributes");
            $attributes = array_keys($reflection->invoke(null));
        } catch (ReflectionException) {
            throw new LogicException("Model class $class has no static get_attributes() function");
        }

        $available = implode(", ", $attributes);

        parent::__construct("Field \"$field\" does not exist in model class \"$class\". Available fields are: $available");
        $this->field = $field;
        $this->class = $class;
    }
}
