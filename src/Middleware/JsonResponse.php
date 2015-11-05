<?php
namespace sideco\middleware;

class JsonResponse
{
    public function __invoke($req, $res, $next)
    {
        $newRes = $res->withHeader('Content-Type', 'application/json;charset=utf8');

        $res = $next($req, $newRes);

        return $res;
    }
}
