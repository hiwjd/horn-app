<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\IP;

class HistoryController {
    
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function chats(Request $req, Response $rsp, $args) {
        //$oid = 3;//$req->getParam("chat_id");
        $oid = $req->getParam("oid");
        $size = $req->getParam("size", 10);
        $page = $req->getParam("page", 1);
        $direction = $req->getParam("direction", "prev"); // prev: 往老的翻 next: 往新的翻

        if($size < 1 || $size > 10) {
            $size = 10;
        }

        $cond = array(
            "oid" => $oid,
            "size" => $size,
            "page" => $page,
            "direction" => $direction
        );

        $r = $this->ci->chat->getChatList($cond);

        return $rsp->withJson(array(
            "code" => 0,
            "msg" => "ok",
            "data" => $r["data"],
            "total" => $r["total"]
        ));
    }

    public function chat(Request $req, Response $rsp, $args) {
        $oid = $req->getParam("oid");
        $cid = $req->getParam("cid");

        $chat = $this->ci->chat->getChat($oid, $cid);

        return $rsp->withJson($chat);
    }

}