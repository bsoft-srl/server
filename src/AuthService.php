<?php

namespace Sideco;

use \Sideco\PostgreSQL as DB;
use \Sideco\Config;
use \Firebase\JWT\JWT;

class AuthService
{
    private static $instance;

    public static function instance()
    {
        if (!self::$instance) self::$instance = new self;
        return self::$instance;
    }

    /**
     *
     */
    public function checkLogin($codiceFiscale, $password) {
        $q = '
            SELECT
                id id_utenza,
                tipologia
            FROM utenze
            WHERE codice_fiscale = :codice_fiscale
                AND password = :password
            LIMIT 1
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':codice_fiscale', $codiceFiscale, \PDO::PARAM_STR);
            $sth->bindParam(':password', md5($password), \PDO::PARAM_STR);
        $sth->execute();

        $result = $sth->fetch();

        return $result;
    }

    /**
    *
    */
    public function authenticate($codiceFiscale, $password)
    {

        $result = $this->checkLogin($codiceFiscale, $password);

        if (!$result) return false;

        $token = [
            'iss' => Config::JWT_ISS,
            'iat' => time(),
            'exp' => time() + 60*60*24, // dura 24 ore
            'id_utenza' => $result->id_utenza,
            'tipologia_utenza' => $result->tipologia
        ];

        $tokenId = JWT::encode($token, Config::JWT_SECRET);

        return $tokenId;
    }

    /**
     *
     */
    public function getTokenId(\Slim\Http\Request $req) {
        $auth = $req->getHeaderLine('Authorization');
        $hasBearer = preg_match('|Bearer (.+)|', $auth, $m);

        if (!$hasBearer) return false;

        $tokenId = $m[1];

        return $tokenId;
    }

    /**
     *
     */
    public function getToken(\Slim\Http\Request $req) {
        $tokenId = $this->getTokenId($req);

        if (!$tokenId) return false;

        try {
            $token = JWT::decode($tokenId, Config::JWT_SECRET, array('HS256'));
            return $token;
        } catch (\Exception $e) {
            return false;
        }
    }
}
