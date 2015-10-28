<?php
namespace Sideco;

use \Sideco\StoreService;
use \Sideco\Middleware\JsonResponse;
use \Sideco\Middleware\VerifyToken;
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
          ->write(json_encode([
            'success' => false,
            'message' => 'Richiesta non valida.'
          ]));
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
          ->write(json_encode([
            'success' => false,
            'message' => 'Metodo per la richiesta non valido. I metodi accettati sono: ' . implode(', ', $methods)
          ]));
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
          ->write(json_encode([
            'success' => false,
            'message' => "La richiesta ha generato un errore inaspettato."
          ]));
    };
};

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

            $numero_contatore = isset($body['numero_contatore']) ? $body['numero_contatore'] : 0;
            $codice_fiscale = isset($body['codice_fiscale']) ? $body['codice_fiscale'] : 0;

            $user = StoreService::instance()->authenticate($numero_contatore, $codice_fiscale);

            if (!$user['success']) return $res->withStatus(403)->write(json_encode($user));

            $token = [
                'iss' => 'localhost:8080',
                'iat' => time(),
                'exp' => time() + 60*60*24, // dura 24 ore
                'payload' => [
                    'numero_contatore' => $numero_contatore
                ]
            ];

            $jwt = JWT::encode($token, Config::JWT_SECRET);

            return $res->write(json_encode([
                'success' => true,
                'message' => 'Autenticazione effettuata con successo.',
                'payload' => [
                  'token' => $jwt
                ]
            ]));
        });

        /**
         *
         */
        $app->get('/units', function ($req, $res) {
          return $res->write(json_encode(StoreService::instance()->units()));
        })->add(new VerifyToken);

        /**
         *
         */
        $app->get('/users', function ($req, $res) {
          $users = StoreService::instance()->users();
          return $res->write(json_encode($users));
        })->add(new VerifyToken);

        /**
         *
         */
        $app->get('/dps[/{start}[/{m:.+}]]', function ($req, $res, $args) {
          $data = StoreService::instance()->dps($args['start'], $args['m']);
          return $res->write(json_encode($data));
        } );
    } );
} );

$app->run();
