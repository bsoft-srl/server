<?php
namespace Sideco\Middleware;

use \Sideco\Config;
use \Firebase\JWT\JWT;

class VerifyToken
{
    public function __invoke($req, $res, $next)
    {
        $auth = $req->getHeaderLine('Authorization');
        $hasBearer = preg_match('|Bearer (.+)|', $auth, $m);

        if ($hasBearer) {
            $jwt = $m[1];

            try {
                $token = JWT::decode($jwt, Config::JWT_SECRET, array('HS256'));
                $next($req, $res);
            } catch (\Exception $e) {
                return $res->withStatus(401)->write(json_encode([
                    'success' => false,
                    'message' => 'Impossibile autenticare il token.'
                ]));
            }
        } else {
            return $res->withStatus(401)->write(json_encode([
                'success' => false,
                'message' => 'Nessun token inoltrato.'
            ]));
        }

        return $res;
    }
}
