<?php

namespace sideco;

use \sideco\DB;
use \sideco\Config;
use \Firebase\JWT\JWT;

class Auth
{
    /**
     *
     */
    public static function checkLogin($codiceFiscale, $password) {
        $q = '
            SELECT
                id,
                tipologia
            FROM utenze
            WHERE codice_fiscale = :codice_fiscale
                AND password = :password
            LIMIT 1
            ';

        $hash = md5($password);

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':codice_fiscale', $codiceFiscale);
            $sth->bindParam(':password', $hash);
        $sth->execute();

        $result = $sth->fetch();

        return $result;
    }

    /**
    *
    */
    public static function authenticate($codiceFiscale, $password)
    {

        $result = self::checkLogin($codiceFiscale, $password);

        if (!$result) return false;

        $token = [
            'iss' => Config::JWT_ISS,
            'iat' => time(),
            'exp' => time() + 60*60*24, // dura 24 ore
            'id_utenza' => $result['id'],
            'tipologia' => $result['tipologia']
        ];

        $tokenId = JWT::encode($token, Config::JWT_SECRET);

        return $tokenId;
    }

    /**
     *
     */
    public static function getTokenId(\Slim\Http\Request $req) {
        $auth = $req->getHeaderLine('Authorization');
        $hasBearer = preg_match('|Bearer (.+)|', $auth, $m);

        if (!$hasBearer) return false;

        $tokenId = $m[1];

        return $tokenId;
    }

    /**
     *
     */
    public static function getToken(\Slim\Http\Request $req) {
        $tokenId = self::getTokenId($req);

        if (!$tokenId) return false;

        try {
            $token = JWT::decode($tokenId, Config::JWT_SECRET, array('HS256'));
            return $token;
        } catch (\Exception $e) {
            return false;
        }
    }
}
