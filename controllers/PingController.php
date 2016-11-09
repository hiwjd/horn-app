<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\Queue;

class PingController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    // 心跳
    public function ping(Request $req, Response $rsp, $args) {
        $uid = $req->getParam("uid");
        $fp = $req->getParam("fp");
        $trackId = $req->getParam("track_id");

        $interval = 30;

        if(!$this->ci->store->checkTimeout($uid, $interval)) {
            $arr = array(
                "uid" => $uid
            );
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
            "msg" => "",
            "interval" => $interval
        ));
    }

}