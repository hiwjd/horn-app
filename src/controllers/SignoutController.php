<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\Staff;
use Horn\CaptachType;
use Horn\Exception;

class SignoutController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function signout(Request $req, Response $rsp, $args) {
        if(isset($_SESSION['staff'])) {
            unset($_SESSION['staff']);
        }

        return $rsp->withJson(Util::BeJson('登出', 0));
    }
}