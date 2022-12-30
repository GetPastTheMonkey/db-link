<?php

namespace Getpastthemonkey\DbLink;

use LogicException;
use PDO;

abstract class Model
{
    abstract protected static function get_table_name(): string;

    abstract protected static function get_attributes(): array;

    private array $data;

    private readonly PDO $PDO;

    private readonly array $attributes;

    public function __construct()
    {
        $this->PDO = DatabaseManager::getPDO();
        $this->data = [];
        $this->attributes = static::get_attributes();
    }

    //////////////////////////////////////////////////
    /// Django-inspired interface

    public static function objects(): Query
    {
        return new Query(static::class);
    }

    public function save(): void
    {
        // TODO: Implement saving (creation and updating)
    }

    //////////////////////////////////////////////////
    /// Magic method overloading

    public function __get(string $name): mixed
    {
        $this->enforce_has_attribute($name);
        return $this->data[$name];
    }

    public function __set(string $name, mixed $value): void
    {
        $this->enforce_has_attribute($name);
        $this->data[$name] = $value;
    }

    //////////////////////////////////////////////////
    /// Internal methods

    private function enforce_has_attribute(string $attr): void
    {
        if (!$this->has_attribute($attr)) {
            throw new LogicException("Model class \"".static::class."\" has no attribute \"".$attr."\"");
        }
    }

    private function has_attribute(string $attr): bool
    {
        return array_key_exists($attr, $this->attributes);
    }
}
