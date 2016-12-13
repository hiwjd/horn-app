<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\Queue;
use Horn\IP;

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
        
        $vid = $req->getParam("vid");
        $fp = $req->getParam("fp");
        $oid = $req->getParam("oid");
        $url = $req->getParam("url");
        $title = $req->getParam("title");
        $referer = $req->getParam("referer");
        $os = $req->getParam("os");
        $browser = $req->getParam("browser");
        $ip = $req->getAttribute('ip_address');
        $addrArr = IP::find($ip);
        $addr = $addrArr[1].$addrArr[2];

        if(!$vid && !$fp) {
            return $rsp->withJson(array(
                "code" => 1,
                "msg" => "缺少必要参数"
            ));
        }

        if(!$vid) {
            $vid = $this->ci->store->mustGetUid($fp);
        }
        $tid = date("YmdHis").$vid.Util::randStr(16);
        
        // 把访问信息存起来
        $viewData = array(
            'tid' => $tid,
            'vid' => $vid,
            'fp' => $fp,
            'oid' => intval($oid),
            'url' => $url,
            'title' => $title,
            'referer' => $referer,
            'os' => $os,
            'browser' => $browser,
            'ip' => $ip,
            'addr' => $addr
        );

        $this->ci->queue->push(Queue::TOPIC_VIEW_PAGE, "#f".json_encode($viewData, JSON_UNESCAPED_UNICODE));

        return $rsp->withJson(array(
            "code" => 0,
            "vid" => $vid,
            "tid" => $tid,
            "fp" => $fp
        ));
    }

}