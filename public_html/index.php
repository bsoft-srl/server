<?php
namespace Sideco;

use \sideco\Store;
use \sideco\middleware\HandleCors;
use \sideco\middleware\JsonResponse;
use \sideco\middleware\VerifyToken;
use \sideco\middleware\SetACL;
use \sideco\Auth;
use \sideco\JsonHelper;

define('SIDECO_INIT', true);
require_once 'autoload.php';

/**
 *
 */
$c = new \Slim\Container();

/**
 *
 */
$c['notFoundHandler'] = function ($c) {
    return function ($req, $res) use ($c) {
        return $c['response']
          ->withStatus(400)
          ->withHeader('Content-type', 'application/json')
          ->write(JsonHelper::fail('Richiesta non valida.'));
    };
};

/**
 *
 */
$c['notAllowedHandler'] = function ($c) {
    return function ($req, $res, $methods) use ($c) {

        /*
         * Se è una richiesta di preflight la lascio passare
         */
        if ($req->isOptions())
            return $res
                ->withStatus(204);

        return $c['response']
          ->withStatus(405)
          ->withHeader('Allow', implode(', ', $methods))
          ->withHeader('Content-type', 'application/json')
          ->write(JsonHelper::fail('Metodo per la richiesta non valido. I metodi accettati sono: ' . implode(', ', $methods)));
    };
};

/**
 *
 */
$c['errorHandler'] = function ($c) {
    return function ($req, $res, $exception) use ($c) {
        return $c['response']
          ->withStatus(500)
          ->withHeader('Content-Type', 'application/json')
          ->write(JsonHelper::fail('La richiesta ha generato un errore inaspettato.'));
    };
};

/**
 *
 */
$app = new \Slim\App($c);
$app->add(new HandleCors);


$app->group('/api', function () use ($app) {
    $app->group('/v1', function () use ($app) {

        $app->add(new JsonResponse);

        /**
         *
         */
        $app->get('/catalog[/{table}]', function ($req, $res, $args) {
            $table = isset($args['table']) ? $args['table'] : null;
            $data = Store::catalog($table);
            return $res->write(JsonHelper::success($data));
        });

        /**
         *
         */
        $app->post('/autenticazione', function ($req, $res) {
            $body = $req->getParsedBody();

            $codiceFiscale = isset($body['codice_fiscale']) ? $body['codice_fiscale'] : '';
            $password = isset($body['password']) ? $body['password'] : '';

            $result = Auth::authenticate($codiceFiscale, $password);

            if (!$result)
                return $res
                    ->withStatus(403)
                    ->write(JsonHelper::fail('Codice Fiscale e/o Password errati.'));

            return $res->write(JsonHelper::success($result));
        });

        /**
         *
         */
        $app->get('/profilo/{id_utenza:\d}', function ($req, $res, $args) {

            $incsQuery = $req->getQueryParams()['include'];
            $idUtenza = $args['id_utenza'];

            $result = Store::getProfilo($idUtenza, $incsQuery);

            $res->write(JsonHelper::success($result));
        })
        ->add(new SetACL)
        ->add(new VerifyToken);

        /**
         *
         */
        $app->get('/profilo/me', function ($req, $res, $args) {

            $incsQuery = $req->getQueryParams()['include'];
            $idUtenza = $args['_id_utenza'];

            $result = Store::getProfilo($idUtenza, $incsQuery);

            $res->write(JsonHelper::success($result));
        })
        ->add(new VerifyToken);

        /**
         *  /api/v1/sensori/838701426/ambientale (temperatura)
         *  /api/v1/sensori/838701426/ambientale/2 (umidità)
         *  /api/v1/sensori/838701426/ambientale/3 (anidrite carbonica)
         *  /api/v1/sensori/838701426/energia_elettrica (kWh)
         *  /api/v1/sensori/838701426/energia_elettrica/2 (energia elettrica reattiva)
         */
        $app->get('/sensori/{numero_contatore}/{metrica}[/{canale}]', function ($req, $res, $args) {

            $queryParams = $req->getQueryParams();

            $numeroContatore = $args['numero_contatore'];
            $metrica = $args['metrica'];
            $canale = isset($args['canale']) ? $args['canale'] : 1;

            $result = Store::getSensoreDataByNumeroContatore($numeroContatore, $metrica, $canale, $queryParams);

            if (!$result)
                return $res
                    ->withStatus(404)
                    ->write(JsonHelper::fail('Impossibile recuperare le informazioni dal sensore.'));

            return $res->write(JsonHelper::success($result));
        })
        ->add(new SetACL)
        ->add(new VerifyToken);
    });
});

$app->run();
