<?php
namespace Sideco;

use \Sideco\StoreService;
use \Sideco\AuthService;
use \Sideco\JsonHelper;
use \Sideco\Middleware\JsonResponse;
use \Sideco\Middleware\VerifyToken;
use \Sideco\Middleware\SetACL;
use \Firebase\JWT\JWT;

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
/*$c['errorHandler'] = function ($c) {
    return function ($req, $res, $exception) use ($c) {
        return $c['response']
          ->withStatus(500)
          ->withHeader('Content-Type', 'application/json')
          ->write(JsonHelper::fail('La richiesta ha generato un errore inaspettato.'));
    };
};*/

/**
 *
 */
$app = new \Slim\App($c);

/**
 *
 */
$app->group('/api', function () use ($app) {
    $app->group('/v1', function () use ($app) {

        /**
         *
         */
        $app->add(new JsonResponse);

        /**
         *
         */
        $app->post('/authenticate', function ($req, $res) {
            $body = $req->getParsedBody();

            $codice_fiscale = isset($body['codice_fiscale']) ? $body['codice_fiscale'] : '';
            $password = isset($body['password']) ? $body['password'] : '';

            $result = AuthService::instance()->authenticate($codice_fiscale, $password);

            if (!$result)
                return $res
                    ->withStatus(403)
                    ->write(JsonHelper::fail('Codice Fiscale e/o Password errati.'));

            return $res->write(JsonHelper::success($result));
        });

        /**
         *
         */
        $app->get('/catalog[/{table}]', function ($req, $res, $args) {
            $table = isset($args['table']) ? $args['table'] : null;
            $data = StoreService::instance()->catalog($table);
            return $res->write(JsonHelper::success($data));
        })->add(new VerifyToken);

        /**
         *
         */
        $app->get('/profilo/corrente', function ($req, $res, $args) {
            $utenzaId = $args['id_utenza_corrente'];
            return $res->write(JsonHelper::success(StoreService::instance()->getProfile($utenzaId)));
        })->add(new VerifyToken);

        /**
         *
         */
        $app->get('/profilo/{id_utenza:\d+}', function ($req, $res, $args) {
            $result = StoreService::instance()->getProfile($args['id_utenza']);

            if (!$result)
                return $res
                    ->withStatus(404)
                    ->write(JsonHelper::fail('Utenza inesistente.'));

            return $res->write(JsonHelper::success($result));
        })
        ->add(new setACL)
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

            $numero_contatore = $args['numero_contatore'];
            $metrica = $args['metrica'];
            $canale = isset($args['canale']) ? $args['canale'] : 1;

            $data = StoreService::instance()->getSensoreDataByNumeroContatore($numero_contatore, $metrica, $canale, $queryParams);

            if (false === $data)
                return $res
                    ->withStatus(404)
                    ->write(JsonHelper::fail('Impossibile recuperare le informazioni dal sensore.'));

            return $res->write(JsonHelper::success($data));
        })
        ->add(new setACL)
        ->add(new VerifyToken);
    } );
} );

$app->run();
