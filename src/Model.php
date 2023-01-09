<?php

namespace Getpastthemonkey\DbLink;

use Getpastthemonkey\DbLink\exceptions\ValidationException;
use Getpastthemonkey\DbLink\fields\Field;
use LogicException;
use PDO;

abstract class Model
{
    abstract protected static function get_table_name(): string;

    /**
     * @return array<string, Field>
     */
    abstract protected static function get_attributes(): array;

    private array $data;

    private readonly PDO $PDO;

    private readonly array $attributes;
    private bool $exists;

    public function __construct()
    {
        $this->PDO = DatabaseManager::getPDO();
        $this->data = [];
        $this->attributes = static::get_attributes();
        $this->exists = false;

        // Set default values
        foreach ($this->attributes as $col => $field) {
            $this->data[$col] = $field->default;
        }
    }

    //////////////////////////////////////////////////
    /// Django-inspired interface

    public static function objects(): Query
    {
        return new Query(static::class);
    }

    /**
     * @throws ValidationException
     */
    public function save(): void
    {
        $this->validate();

        if ($this->exists) {
            // TODO: Implement UPDATE
            $raw_sql = "UPDATE " . static::get_table_name() . " SET XX WHERE pk = pk_value";
            $parameters = $this->data;
        } else {
            $columns = array_keys($this->attributes);
            $columns_imploded = implode(", ", $columns);

            $question_marks = array_fill(0, count($this->attributes), "?");
            $question_marks_imploded = implode(", ", $question_marks);

            $raw_sql = "INSERT INTO " . static::get_table_name() . " (" . $columns_imploded . ") VALUES (" . $question_marks_imploded . ")";
            $parameters = $this->data;
        }

        // FIXME: Problem with NULL values --> Number of parameters does not match!
        $stmt = $this->PDO->prepare($raw_sql);
        $stmt->execute($parameters);
    }

    /**
     * @throws ValidationException
     */
    public function validate(): void
    {
        $errors = $this->get_validation_errors();

        if (count($errors) > 0) {
            throw new ValidationException("Nested validation error for " . static::class . " model", $errors);
        }
    }

    /**
     * @return array<string, ValidationException>
     */
    public function get_validation_errors(): array
    {
        $errors = array();

        foreach ($this->attributes as $col => $field) {
            try {
                $field->validate($this->data[$col]);
            } catch (ValidationException $e) {
                $errors[$col] = $e;
            }
        }

        return $errors;
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

    public function set_existing(): void
    {
        $this->exists = true;
    }

    private function enforce_has_attribute(string $attr): void
    {
        if (!$this->has_attribute($attr)) {
            throw new LogicException("Model class \"" . static::class . "\" has no attribute \"" . $attr . "\"");
        }
    }

    private function has_attribute(string $attr): bool
    {
        return array_key_exists($attr, $this->attributes);
    }
}
