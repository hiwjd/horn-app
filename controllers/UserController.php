<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;

class UserController {

    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function online(Request $req, Response $rsp, $args) {
        $uids = $this->ci->store->getOnlineUsers();

        return $rsp->withJson(array(
            "code" => 0,
            "uids" => $uids
        ));
    }

}