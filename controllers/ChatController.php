<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ChatController {
    
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function message(Request $req, Response $rsp, $args) {
        $body = $req->getBody();
        
        $m = $this->ci->chat->dispatchMsg($body);

        return $rsp->withJson(array(
            "code" => 0,
            "mid" => $m["mid"],
            "msg" => "ok"
        ));
    }

}