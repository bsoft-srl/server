<?php

namespace sideco\middleware;

class HandleCors {
    public function __invoke($req, $res, $next) {
        $res = $res->withHeader('Access-Control-Allow-Origin', '*');

        if ($req->isOptions()) {
            $res = $res
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                ->withStatus(204);
        }
        return $next($req, $res);
    }
}
