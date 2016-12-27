<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\Queue;
use Horn\IdGen;

class HeartbeatController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    // 心跳
    public function heartbeat(Request $req, Response $rsp, $args) {
        $oid = $req->getParam("oid");
        $uid = $req->getParam("uid");
        $fp = $req->getParam("fp");
        $tid = $req->getParam("tid");
        $interval = $req->getParam("interval", 30);
        $tolerance = $req->getParam("tolerance", 3);

        if(!$this->ci->store->checkTimeout($uid, $interval, $tolerance)) {
            $arr = array(
                "type" => "timeout",
                "oid" => $oid,
                "uid" => $uid
            );
            $arr["mid"] = IdGen::next(); // 先生成消息ID
            $payload = "#g".json_encode($arr, JSON_UNESCAPED_UNICODE);
            $this->ci->queue->push(Queue::TOPIC_TIMEOUT, $payload); // 通知超时了
            return $rsp->withJson(array(
                "code" => 1,
                "msg" => "",
                "interval" => $interval
            ));
        }

        return $rsp->withJson(array(
            "code" => 0,
            "msg" => "ok",
            "interval" => $interval
        ));
    }

}