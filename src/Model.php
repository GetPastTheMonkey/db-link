<?php

namespace Getpastthemonkey\DbLink;

use PDO;

abstract class Model
{
    abstract protected static function get_table_name(): string;

    abstract protected static function get_attributes(): array;

    private readonly PDO $PDO;

    public function __construct()
    {
        $this->PDO = DatabaseManager::getPDO();
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
}
