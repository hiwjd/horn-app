<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\CaptachType;

class TestController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function abc(Request $req, Response $rsp, $args) {
        //$_SESSION["name"] = "abc";
        $input = file_get_contents("php://input");
        $this->ci->logger->info("<abc> $input");

        return $rsp->getBody()->write($input."\r\n");
    }
}