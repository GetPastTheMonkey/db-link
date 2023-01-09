<?php

namespace Getpastthemonkey\DbLink;

use LogicException;
use PDO;

final class DatabaseManager
{
    private static ?PDO $PDO = null;

    public static function getPDO(): PDO
    {
        if (is_null(self::$PDO)) {
            // Create new PDO connection
            $function = "dblink_create_pdo";
            if (!function_exists($function)) {
                throw new LogicException("The function \"$function\" was not found");
            }

            self::$PDO = call_user_func($function);
        }

        return self::$PDO;
    }
}
