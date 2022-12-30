<?php

namespace Getpastthemonkey\DbLink;

use LogicException;
use PDO;

abstract class Model
{
    protected static ?string $TABLE_NAME = NULL;

    protected ?array $ATTRIBUTES = NULL;

    private readonly PDO $PDO;

    public function __construct()
    {
        $this->PDO = DatabaseManager::getPDO();
    }

    //////////////////////////////////////////////////
    /// Django-inspired interface

    public static function objects(): Query
    {
        if (is_null(self::$TABLE_NAME)) {
            throw new LogicException("Subclass of Model must have a table name configured");
        }

        return new Query(self::class);
    }

    public function save(): void
    {
        // TODO: Implement saving (creation and updating)
    }
}
