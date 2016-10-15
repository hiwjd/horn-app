<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Horn\Util;
use Horn\Staff;
use Horn\CaptachType;
use Horn\Exception;

class SignoutController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function signout($req, $rsp, $args) {
        if(isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }

        return $rsp->withJson(Util::BeJson('登出', 0));
    }
}