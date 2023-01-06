<?php

namespace Getpastthemonkey\DbLink;

use AssertionError;
use Countable;
use Getpastthemonkey\DbLink\filters\F_AND;
use Getpastthemonkey\DbLink\filters\F_NOT;
use Getpastthemonkey\DbLink\filters\Filter;
use Iterator;
use LogicException;
use PDO;
use ReflectionException;
use ReflectionMethod;

final class Query implements Countable, Iterator
{
    private readonly string $model_class;
    private readonly PDO $PDO;

    // Variables for building the SQL statement
    private ?Filter $filter = null;
    private array $order_fields = array();
    private array $order_directions = array();
    private int $limit = 0;
    private int $offset = 0;

    // Data storage
    private ?array $data = null;
    private int $data_index = 0;

    public function __construct(string $model_class)
    {
        $this->model_class = $model_class;
        $this->PDO = DatabaseManager::getPDO();
    }

    private function fetch(): void
    {
        if (!is_null($this->data)) {
            return;
        }

        try {
            $table_property = new ReflectionMethod($this->model_class, "get_table_name");
            $table_name = $table_property->invoke(null);
        } catch (ReflectionException) {
            throw new LogicException("Model \"$this->model_class\" does not have a get_table_name() function");
        }

        // Build SQL statement
        $raw_sql = "SELECT * FROM $table_name";
        if (!is_null($this->filter))        $raw_sql .= PHP_EOL . "WHERE " . $this->filter->get_where_clause();
        if (count($this->order_fields) > 0) $raw_sql .= PHP_EOL . "ORDER BY " . $this->get_order_by_clause();
        if ($this->limit > 0)               $raw_sql .= PHP_EOL . "LIMIT $this->offset, $this->limit";

        // Prepare SQL statement
        $stmt = $this->PDO->prepare($raw_sql);

        // Execute SQL statement
        if (is_null($this->filter)) {
            $stmt->execute();
        } else {
            $stmt->execute($this->filter->get_parameters());
        }

        // Fetch all results
        $this->data = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $this->model_class);
        $this->data_index = 0;
    }

    private function invalidate(): void
    {
        $this->data = null;
    }

    private function get_order_by_clause(): string
    {
        if (count($this->order_fields) != count($this->order_directions)) {
            throw new AssertionError("Count of order fields and order directions does not match!");
        }

        $arr = array();
        $combined = array_map(null, $this->order_fields, $this->order_directions);

        foreach ($combined as list($field, $ascending)) {
            $direction = $ascending ? "ASC" : "DESC";
            $arr[] = "$field $direction";
        }

        return implode(", ", $arr);
    }

    //////////////////////////////////////////////////
    /// Django-inspired interface

    public function filter(Filter $filter): Query
    {
        $this->invalidate();
        if (is_null($this->filter)) {
            $this->filter = $filter;
        } else {
            $this->filter = new F_AND($this->filter, $filter);
        }
        return $this;
    }

    public function exclude(Filter $filter): Query
    {
        return $this->filter(new F_NOT($filter));
    }

    public function limit(int $limit, int $offset = 0): Query
    {
        if ($limit < 0) {
            throw new LogicException("Query limit cannot be lower than 0");
        }

        if ($offset < 0) {
            throw new LogicException("Query offset cannot be lower than 0");
        }

        $this->invalidate();
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function order(string $field, bool $ascending): Query
    {
        $this->invalidate();

        $this->order_fields[] = $field;
        $this->order_directions[] = $ascending;

        return $this;
    }

    //////////////////////////////////////////////////
    /// Countable interface

    /**
     * Count number of model entities returned by the query
     * @return int The number of model entities returned by the query
     */
    public function count(): int
    {
        $this->fetch();
        return count($this->data);
    }

    //////////////////////////////////////////////////
    /// Iterator interface

    /**
     * Return the current model entity
     * @return Model The current model entity
     * @throws LogicException Is thrown if the iterator is invalid
     */
    public function current(): Model
    {
        $this->fetch();

        if ($this->valid()) {
            return $this->data[$this->data_index];
        } else {
            throw new LogicException("Current index is invalid");
        }
    }

    /**
     * Move forward to the next model entity
     * @return void
     */
    public function next(): void
    {
        $this->fetch();
        $this->data_index += 1;
    }

    /**
     * Return the key of the current model entity
     * @return int|null Key of the current model entity, or null if the iterator is invalid
     */
    public function key(): ?int
    {
        $this->fetch();
        if ($this->valid()) {
            return $this->data_index;
        } else {
            return null;
        }
    }

    /**
     * Checks if the current iterator position is valid
     * @return bool Returns true if the current position is valid, false otherwise.
     */
    public function valid(): bool
    {
        $this->fetch();
        return (0 <= $this->data_index) and ($this->data_index < count($this->data));
    }

    /**
     * Rewind the iterator to the first element
     * @return void
     */
    public function rewind(): void
    {
        $this->fetch();
        $this->data_index = 0;
    }
}
