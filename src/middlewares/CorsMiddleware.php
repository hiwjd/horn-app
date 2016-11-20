<?php
namespace Middleware;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class CorsMiddleware {

    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $req, Response $rsp, callable $next) {
        $newrsp = $rsp->withHeader("Access-Control-Allow-Origin", "*")
                    ->withHeader("Access-Control-Allow-Methods", "GET,POST")
                    ->withHeader("Access-Control-Allow-Headers", "content-type");
        return $next($req, $newrsp);
    }

}