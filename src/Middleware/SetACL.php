<?php
namespace Sideco\Middleware;

use \Sideco\AuthService;
use \Sideco\StoreService;
use \Sideco\JsonHelper;


class SetACL
{
    private $allowed = [];

    /**
     *
     */
    public function __construct(array $allowed = []) {
        $this->allowed = $allowed;
    }

    /**
     *
     */
    public function __invoke($req, $res, $next)
    {
        $route = $req->getAttributes()['route'];
        $args = $route->getArguments();

        //
        $trusted = false;

        /*
         * Per verificare che la risorsa richiesta afferisca all'utenza corrente
         * è indispensabile conoscere l'id_utenza_corrente,
         * argomento creato a seguito della chiamata al middleware VerifyToken
         */
        if (!isset($args['id_utenza_corrente']))
            return $res
                ->withStatus(403)
                ->write(JsonHelper::fail('Impossibile verificare i permessi.'));

        /*
         * Se si interroga per id_utenza, verifico che l'utenza corrente sia
         * la medesima di quella interrogata
         */
        if (
            isset($args['id_utenza']) &&
            $args['id_utenza'] == $args['id_utenza_corrente']
        ) { $trusted = true; }

        /*
         * Se si interroga un numero_contatore, verifico che l'utenza corrente
         * sia la proprietaria
         */
        else if (
            isset($args['numero_contatore']) &&
            in_array(
                $args['numero_contatore'],
                StoreService::instance()->getNumeroContatoriByUtenza($args['id_utenza_corrente'])
                )
        ) { $trusted = true; }

        // Se sono il proprietario posso accedere alla risorsa
        if ($trusted) {
            $route->setArgument('is_owner', true);
            return $next($req, $res);
        }

        /*
         * Se non sono il proprietario della risorsa richiesta, posso far parte
         * di una tipologia di utenza in grado comunque di avere accesso alle
         * informazioni interrogate.
         */
        if (
            in_array(
                $args['tipologia_utenza_corrente'],
                $this->allowed
            )
        ) { $trust = true; }


        if (!$trust)
            return $res
                ->withStatus(401)
                ->write(JsonHelper::fail('Non possiedi i permessi per completare l\'operazione.'));

        $route->setArgument('is_owner', false);
        return $next($req, $res);
    }
}