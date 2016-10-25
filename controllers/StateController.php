<?php

namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class StateController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function check(Request $req, Response $rsp, $args) {
        if(isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            return $rsp->withJson(array('code' => 0));
        } else {
            return $rsp->withJson(array('code' => 1009));
        }
    }
}