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
            $config_file = __DIR__."/dblink-config.php";
            if (!is_file($config_file)) {
                throw new LogicException("No configuration file found. Tried $config_file");
            }

            require_once $config_file;

            $function = "create_pdo";
            if (!function_exists($function)) {
                throw new LogicException("Config file does not contain the function \"$function\"");
            }

            self::$PDO = call_user_func($function);
        }

        return self::$PDO;
    }
}
