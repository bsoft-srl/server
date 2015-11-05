<?php
namespace sideco;

use \sideco\Config;

class DB
{
    private static $instance;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new \PDO(
                sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    Config::SIDECO_HOST,
                    Config::PGS_PORT,
                    Config::PGS_DBNAME
                ),
                Config::PGS_USER,
                Config::PGS_PASSWD
            );

            self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }

        return self::$instance;
    }

    public function __destruct()
    {
        self::$instance = null;
    }
}
