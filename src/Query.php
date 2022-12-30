<?php

namespace Getpastthemonkey\DbLink;

use Countable;
use Iterator;
use LogicException;
use PDO;
use ReflectionException;
use ReflectionMethod;

final class Query implements Countable, Iterator
{
    private readonly string $model_class;
    private readonly PDO $PDO;

    private array $filters;
    private array $orders;
    private int $limit;

    // Data storage
    private ?array $data = NULL;
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

        $stmt = $this->PDO->prepare("SELECT * FROM $table_name");
        $stmt->execute();
        $this->data = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $this->model_class);
        $this->data_index = 0;
    }

    private function invalidate(): void
    {
        $this->data = NULL;
    }

    //////////////////////////////////////////////////
    /// Django-inspired interface

    public function filter(): Query
    {
        $this->invalidate();
        return $this;
    }

    public function exclude(): Query
    {
        $this->invalidate();
        return $this;
    }

    public function limit(): Query
    {
        $this->invalidate();
        return $this;
    }

    public function order(): Query
    {
        $this->invalidate();
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
     * @return int|null Key of the current model entity, or NULL if the iterator is invalid
     */
    public function key(): ?int
    {
        $this->fetch();
        if ($this->valid())
        {
            return $this->data_index;
        }
        else
        {
            return NULL;
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
