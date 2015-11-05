<?php
namespace sideco\middleware;

use \sideco\Store;
use \sideco\JsonHelper;


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
         * è indispensabile conoscere l'_id_utenza corrente,
         * argomento creato a seguito della chiamata al middleware VerifyToken
         */
        if (!isset($args['_id_utenza']))
            return $res
                ->withStatus(403)
                ->write(JsonHelper::fail('Impossibile verificare i permessi.'));

        /*
         * Se si interroga per id_utenza, verifico che l'utenza corrente sia
         * la medesima di quella interrogata
         */
        if (
            isset($args['id_utenza']) &&
            $args['id_utenza'] == $args['_id_utenza']
        ) { $trusted = true; }

        /*
         * Se si interroga un numero_contatore, verifico che l'utenza corrente
         * sia la proprietaria
         */
        else if (
            isset($args['numero_contatore']) &&
            in_array(
                $args['numero_contatore'],
                Store::getNumeroContatoriByIdUtenza($args['_id_utenza'])
                )
        ) { $trusted = true; }

        // Se sono il proprietario posso accedere alla risorsa
        if ($trusted) {
            $route->setArgument('_proprietario', true);
            return $next($req, $res);
        }

        /*
         * Se non sono il proprietario della risorsa richiesta, posso far parte
         * di una tipologia di utenza in grado comunque di avere accesso alle
         * informazioni interrogate.
         */
        if (
            in_array(
                $args['_tipologia'],
                $this->allowed
            )
        ) { $trusted = true; }

        if (!$trusted)
            return $res
                ->withStatus(401)
                ->write(JsonHelper::fail('Non possiedi i permessi per completare l\'operazione.'));

        $route->setArgument('_proprietario', false);
        return $next($req, $res);
    }
}
