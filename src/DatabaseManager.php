<?php

namespace Getpastthemonkey\DbLink;

use PDO;

final class DatabaseManager
{
    private static ?PDO $PDO = null;

    public static function getPDO(): PDO
    {
        if (is_null(self::$PDO)) {
            // Create new PDO connection
            // TODO: Implement me!
        }

        return self::$PDO;
    }
}
