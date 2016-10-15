<?php

namespace Controller;

use Slim\Container as ContainerInterface;

class StateController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function check($req, $rsp, $args) {
        if(isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            return $rsp->withJson(array('code' => 1000));
        } else {
            return $rsp->withJson(array('code' => 1009));
        }
    }
}