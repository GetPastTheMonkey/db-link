<?php

namespace Getpastthemonkey\DbLink;

use ArrayAccess;
use Getpastthemonkey\DbLink\exceptions\FieldDoesNotExistException;
use Getpastthemonkey\DbLink\exceptions\ValidationException;
use Getpastthemonkey\DbLink\fields\Field;
use Getpastthemonkey\DbLink\fields\IntegerField;
use Iterator;
use PDO;
use ReflectionClass;
use Stringable;

abstract class Model implements ArrayAccess, Stringable, Iterator
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
    private array $pk_cache;
    private int $iter_idx;

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

        $this->pk_cache = array();
        $this->iter_idx = 0;
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

        $columns = array_keys($this->attributes);
        $parameters = array_values($this->data);

        if ($this->exists) {
            $fn = fn($col) => "$col = ?";

            $attributes = array_map($fn, $columns);
            $attributes_imploded = implode(", ", $attributes);

            $pk_attrs = array_map($fn, $this->get_primary_key_columns());
            $pk_attrs_imploded = implode(" AND ", $pk_attrs);

            $raw_sql = "UPDATE " . static::get_table_name() . " SET $attributes_imploded WHERE $pk_attrs_imploded";
            $parameters = array_merge($parameters, array_values($this->pk_cache));
        } else {
            $columns_imploded = implode(", ", $columns);

            $question_marks = array_fill(0, count($this->attributes), "?");
            $question_marks_imploded = implode(", ", $question_marks);

            $raw_sql = "INSERT INTO " . static::get_table_name() . " (" . $columns_imploded . ") VALUES (" . $question_marks_imploded . ")";
        }

        $stmt = $this->PDO->prepare($raw_sql);
        $stmt->execute($parameters);

        // Get auto increment value if it was an insert and there is an auto increment value to get
        if (!$this->exists) {
            foreach ($this->attributes as $col => $field) {
                if ($field instanceof IntegerField and $field->is_auto_increment) {
                    $this[$col] = $this->PDO->lastInsertId();

                    // Only one column may be an AUTO_INCREMENT, so it is safe to break here
                    break;
                }
            }
        }

        // Mark the model instance as existing because the save was successful
        // This also updates the internal primary key storage to the new values
        $this->set_existing();
    }

    public function delete(): void
    {
        if (!$this->exists) {
            return;
        }

        $pk_attrs = array_map(fn($col) => "$col = ?", $this->get_primary_key_columns());
        $pk_attrs_imploded = implode(" AND ", $pk_attrs);
        $params = array_values($this->pk_cache);

        $raw_sql = "DELETE FROM " . static::get_table_name() . " WHERE $pk_attrs_imploded";
        $stmt = $this->PDO->prepare($raw_sql);
        $stmt->execute($params);

        // Mark model instance as non-existing, so next save will create instead of update
        $this->exists = false;
    }

    /**
     * @throws ValidationException
     */
    public function validate(): void
    {
        $errors = $this->get_validation_errors();

        if (count($errors) > 0) {
            throw new ValidationException("Validation error for " . static::class . " model", $errors);
        }
    }

    /**
     * @return array<string, ValidationException>
     */
    private function get_validation_errors(): array
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

    private function get_primary_key_columns(): array
    {
        $arr = array();

        foreach ($this->attributes as $col => $field) {
            if ($field->is_primary_key) {
                $arr[] = $col;
            }
        }

        return $arr;
    }

    //////////////////////////////////////////////////
    /// Magic method overloading

    /**
     * @throws FieldDoesNotExistException
     */
    public function __get(string $name): mixed
    {
        return $this->offsetGet($name);
    }

    /**
     * @throws FieldDoesNotExistException
     */
    public function __set(string $name, mixed $value): void
    {
        $this->offsetSet($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * @throws FieldDoesNotExistException
     */
    public function __unset(string $name): void
    {
        $this->offsetUnset($name);
    }

    public function __toString(): string
    {
        $existing = $this->exists ? "existing" : "new";
        $short_name = (new ReflectionClass(static::class))->getShortName();
        $pk_arr = array();

        foreach ($this->get_primary_key_columns() as $col) {
            $pk_arr[] = $col . "=" . $this->offsetGet($col);
        }

        $pk_arr_imploded = implode(", ", $pk_arr);
        return "$short_name model instance ($existing, $pk_arr_imploded)";
    }

    //////////////////////////////////////////////////
    /// Internal methods

    public function set_existing(): void
    {
        $this->exists = true;

        foreach ($this->get_primary_key_columns() as $col) {
            $this->pk_cache[$col] = $this->data[$col];
        }
    }

    /**
     * @throws FieldDoesNotExistException
     */
    private function enforce_has_attribute(string $attr): void
    {
        if (!$this->offsetExists($attr)) {
            throw new FieldDoesNotExistException($attr, static::class);
        }
    }

    //////////////////////////////////////////////////
    /// ArrayAccess interface

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists((string)$offset, $this->attributes);
    }

    /**
     * @throws FieldDoesNotExistException
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->enforce_has_attribute($offset);
        return $this->data[$offset];
    }

    /**
     * @throws FieldDoesNotExistException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->enforce_has_attribute($offset);
        $this->data[$offset] = $value;
    }

    /**
     * @throws FieldDoesNotExistException
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->enforce_has_attribute($offset);
        $this->offsetSet($offset, $this->attributes[$offset]->default);
    }

    public function current(): mixed
    {
        return $this->data[$this->key()];
    }

    public function next(): void
    {
        $this->iter_idx += 1;
    }

    public function key(): string
    {
        $attr_keys = array_keys($this->attributes);
        return $attr_keys[$this->iter_idx];
    }

    public function valid(): bool
    {
        return 0 <= $this->iter_idx and $this->iter_idx < count($this->attributes);
    }

    public function rewind(): void
    {
        $this->iter_idx = 0;
    }
}
