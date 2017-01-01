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

        $id = json_encode(array("oid"=>$oid,"uid"=>$uid), JSON_UNESCAPED_UNICODE);
        $this->ci->redis->zadd("HB_TIMEOUT", time(), $id);

        return $rsp->withJson(array(
            "code" => 0,
            "msg" => "ok",
            "interval" => $interval
        ));
    }

}