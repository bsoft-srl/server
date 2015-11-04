<?php
namespace sideco\middleware;

use \sideco\Config;
use \sideco\Auth;
use \sideco\Store;
use \sideco\JsonHelper;

/**
 * @author Federico Bassi <effe@dupl.it>
 */
class VerifyToken
{
    public function __invoke($req, $res, $next)
    {
        $token = Auth::getToken($req);

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
        $payloadAtteso = ['id_utenza', 'tipologia'];
        foreach ($payloadAtteso as $k) {
            if (!isset($token->$k))
                return $res
                    ->withStatus(401)
                    ->write(JsonHelper::fail('Il token ha un payload non conforme alle aspettative.'));
        }

        /*
         * Se il token è corretto, verifico che il payload id
         * faccia riferimento ad un utente realmente esistente
         */
        $result = Store::getUtenzaById($token->id_utenza);

        if (!$result)
            return $res
                ->withStatus(404)
                ->write(JsonHelper::fail('Utente inesistente.'));

        /*
         * Imposto gli argomenti dell'utenza corrente
         */
        $route = $req->getAttributes()['route'];
        $route->setArgument('_id_utenza', (string)$result['id']);
        $route->setArgument('_tipologia', $result['tipologia']);

        return $next($req, $res);
    }
}
