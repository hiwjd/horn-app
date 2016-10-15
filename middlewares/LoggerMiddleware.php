<?php
namespace Middleware;

class LoggerMiddleware {

    protected $ci;

    public function __construct($ci) {
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
    public function __invoke($req, $rsp, $next) {
        $this->ci->logger->info("aaaaaa=====");
        //$rsp->getBody()->write('BEFORE');
        $rsp = $next($req, $rsp);
        //$rsp->getBody()->write('AFTER');

        return $rsp;
    }

}