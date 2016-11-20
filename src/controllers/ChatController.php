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
        
        // ajax跨域POST时，如果提交的content-type不是application/x-www-form-urlencoded
        // 会被视为preflight请求，这个请求会先发一个OPTIONS请求来试探请求是否被服务端允许
        // 如果允许的，才发出真的请求
        // 一个办法是请求头中的content-type使用application/x-www-form-urlencoded,
        // 请求体还是整个json字符串，现在使用这个方式，但是不知道是不是有浏览器会来检查请求体，
        // 发现不合法后禁止请求，真出现这个情况的话，改下面这个办法，改动只在这里和客户端提交那里
        // 一个办法是请求改成表单形式
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS#Preflighted_requests
        //$body = $req->getParam("d");
        
        $m = $this->ci->chat->dispatchMsg($body);
        $arr = array(
            "code" => 0,
            "mid" => $m["mid"],
            "msg" => "ok"
        );

        switch ($m["type"]) {
            case "request_chat":
                $arr["chat_id"] = $m["event"]["chat"]["id"];
                break;
            
            default:
                # code...
                break;
        }

        return $rsp->withJson($arr);
    }

    public function messages(Request $req, Response $rsp, $args) {
        $chatId = $req->getParam("chat_id");
        $mid = $req->getParam("mid");
        $limit = $req->getParam("limit", 20);
        $direction = $req->getParam("direction", "prev"); // prev: 往老的翻 next: 往新的翻

        if($limit < 1 || $limit > 100) {
            $limit = 100;
        }

        $cond = array(
            "chatId" => $chatId,
            "mid" => $mid,
            "limit" => $limit,
            "direction" => $direction
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