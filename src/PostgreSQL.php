<?php
namespace Sideco;

use \Sideco\Config;

class PostgreSQL
{
    private static $instance;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new \PDO( 'pgsql:host=' . Config::SIDECO_HOST . ';port=' . Config::PGS_PORT . ';dbname=' . Config::PGS_DBNAME, Config::PGS_USER, Config::PGS_PASSWD );
            self::$instance->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
        }

        return self::$instance;
    }

    public function __destruct()
    {
        self::$instance = null;
    }
}
