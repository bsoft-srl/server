<?php
namespace Sideco\Middleware;

use \Sideco\Config;
use \Sideco\AuthService;
use \Sideco\StoreService;
use \Sideco\JsonHelper;

/**
 * @author Federico Bassi <effe@dupl.it>
 */
class VerifyToken
{
    public function __invoke($req, $res, $next)
    {
        $token = AuthService::instance()->getToken($req);

        /*
         * Il token può essere false in una delle seguenti circostanze
         *  1-nessun header di autenticazione inviato con la richiesta
         *  2-il token non è valido
         */
        if (!$token)
            return $res
                ->withStatus(401)
                ->write(JsonHelper::fail('Impossibile autenticare il token.'));

        /*
         * Verifico che il token sia del tipo atteso
         */
        $payloadAtteso = ['id_utenza', 'tipologia_utenza'];
        foreach ($payloadAtteso as $k) {
            if (!isset($token->$k))
                return $res
                    ->withStatus(401)
                    ->write(JsonHelper::fail('Il token ha un payload non conforme alle aspettative.'));
        }

        /*
         * Se il token è corretto, verifico che il payload id_utenza
         * faccia riferimento ad un utente realmente esistente
         */
        $result = StoreService::instance()->getUtenza($token->id_utenza);

        if (!$result)
            return $res
                ->withStatus(404)
                ->write(JsonHelper::fail('Utente inesistente.'));

        /*
         * Imposto gli argomenti dell'utenza corrente
         */
        $route = $req->getAttributes()['route'];
        $route->setArgument('id_utenza_corrente', (string)$result->id_utenza);
        $route->setArgument('tipologia_utenza_corrente', $result->tipologia);

        return $next($req, $res);
    }
}
