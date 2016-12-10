<?php

namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class StateController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function check(Request $req, Response $rsp, $args) {
        if(isset($_SESSION['staff']) && is_array($_SESSION['staff'])) {
            return $rsp->withJson(array('code' => 0));
        } else {
            return $rsp->withJson(array('code' => 1009));
        }
    }

    public function init(Request $req, Response $rsp, $args) {
        $uid = $req->getParam("uid");
        $fp = $req->getParam("fp");
        $tid = $req->getParam("tid");
        
        $store = $this->ci->store;

        // 获取状态数据
        $state = $store->getState($uid);

        // 查看uid有没有分配过pusher
        // 如果已经分配过pusher那么直接使用这个pusher
        // 否则挑选最空的pusher，通知pusher这个uid会从你这获取消息
        // 返回pusher地址
        $pusherAddr = $store->getPusherByUid($uid);
        //if($pusherAddr == "") {
            $pusherAddr = $store->assignIdlePusher($uid);
            if($pusherAddr == "") {
                return $rsp->withJson(array(
                    "code" => 1,
                    "msg" => "分配推送服务器失败",
                    "uid" => $uid,
                    "addr" => $pusherAddr,
                    "tid" => $tid
                ));
            }

            // 告诉pusher有新的uid了
            if(!$this->ci->rpc->joinPusher($pusherAddr, $uid)) {
                return $rsp->withJson(array(
                    "code" => 1,
                    "msg" => "加入推送服务器失败",
                    "uid" => $uid,
                    "addr" => $pusherAddr,
                    "tid" => $tid
                ));
            }
        //}

        return $rsp->withJson(array(
            "code" => 0,
            "uid" => $uid,
            "addr" => $pusherAddr,
            "tid" => $tid,
            "state" => $state
        ));
    }
}