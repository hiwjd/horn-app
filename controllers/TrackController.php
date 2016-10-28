<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\Queue;

class TrackController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function track(Request $req, Response $rsp, $args) {
        // 立即放到队列里去，消费队列去存数据库
        // 筛选相对空闲的pusher，通知这个pusher有人会到他那取消息
        // 维护用户列表
        // 返回这个pusher的地址
        // 客户端后续的消息通过这个pusher得到
        
        $uid = $req->getParam("uid");
        $fp = $req->getParam("fp");
        $group = $req->getParam("group");
        $url = $req->getParam("url");
        $title = $req->getParam("title");
        $referer = $req->getParam("referer");
        $os = $req->getParam("os");
        $browser = $req->getParam("browser");
        $ip = $req->getAttribute('ip_address');
        $trackId = date("YmdHis").str_pad($uid, 9, "0", STR_PAD_LEFT).Util::randStr(16);

        if(!$uid/* || !$fp || !$group*/) {
            return $rsp->withJson(array(
                "code" => 1,
                "msg" => "缺少必要参数"
            ));
        }
        
        // 把访问信息存起来
        $viewData = array(
            'track_id' => $trackId,
            'uid' => $uid,
            'fp' => $fp,
            'group' => $group,
            'url' => $url,
            'title' => $title,
            'referer' => $referer,
            'os' => $os,
            'browser' => $browser,
            'ip' => $ip
        );

        $this->ci->queue->push(Queue::TOPIC_VIEW_PAGE, json_encode($viewData, JSON_UNESCAPED_UNICODE));

        // 查看uid有没有分配过pusher
        // 如果已经分配过pusher那么直接使用这个pusher
        // 否则挑选最空的pusher，通知pusher这个uid会从你这获取消息
        // 返回pusher地址
        
        $store = $this->ci->store;

        $pusherAddr = $store->getPusherByUid($uid);
        if($pusherAddr == "") {
            $pusherAddr = $store->assignIdlePusher($uid);
            if($pusherAddr == "") {
                return $rsp->withJson(array(
                    "code" => 1,
                    "msg" => "分配推送服务器失败",
                    "uid" => $uid,
                    "addr" => $pusherAddr,
                    "track_id" => $trackId
                ));
            }

            // 告诉pusher有新的uid了
            if(!$this->ci->rpc->joinPusher($pusherAddr, $uid)) {
                return $rsp->withJson(array(
                    "code" => 1,
                    "msg" => "加入推送服务器失败",
                    "uid" => $uid,
                    "addr" => $pusherAddr,
                    "track_id" => $trackId
                ));
            }
        }

        return $rsp->withJson(array(
            "code" => 0,
            "uid" => $uid,
            "addr" => $pusherAddr,
            "track_id" => $trackId
        ));
    }

}