<?php
namespace Sideco\Middleware;

class JsonResponse
{
    public function __invoke($req, $res, $next)
    {
        $newRes = $res->withHeader('Content-Type', 'application/json');

        $res = $next($req, $newRes);

        return $res;
    }
}
