<?php
namespace Middleware;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class LoginCheckMiddleware {

    public function __construct() {
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
        if(isset($_SESSION["staff"]) && is_array($_SESSION["staff"])) {
        } else {
            return $rsp->withJson(array('code' => 1009));
        }

        $req = $req->withAttribute("sid", $_SESSION["staff"]["sid"]);
        return $next($req, $rsp);
    }

}