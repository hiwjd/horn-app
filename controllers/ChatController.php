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

    public function messages(Request $req, Response $rsp, $args) {
        $chatId = $req->getParam("chat_id");
        $mid = $req->getParam("mid");
        $limit = $req->getParam("limit", 20);
        $style = $req->getParam("style", "prev"); // prev: 往老的翻 next: 往新的翻

        if($limit < 1 || $limit > 100) {
            $limit = 100;
        }

        $cond = array(
            "chatId" => $chatId,
            "mid" => $mid,
            "limit" => $limit,
            "style" => $style
        );

        $r = $this->ci->chat->getMessages($cond);

        return $rsp->withJson(array(
            "code" => 0,
            "msg" => "ok",
            "data" => $r["data"],
            "tot" => $r["tot"]
        ));
    }

}