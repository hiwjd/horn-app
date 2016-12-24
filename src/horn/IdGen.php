<?php
namespace Horn;

use GuzzleHttp;

class IdGen {

    // 消息ID专用
    public static function next() {
        $url = "http://127.0.0.1:8888/next";
        $client = new GuzzleHttp\Client();
        $res = $client->request("GET", $url);
        if($res->getStatusCode() == 200) {
            return $res->getBody()->getContents();
        }
        return false;
    }

    // 公司ID
    public static function oid() {
        return Util::randStr(15);
    }

    // 客服ID
    public static function sid() {
        return Util::randStr(19);
    }

    // 访客ID
    public static function uid() {
        return Util::randStr(23);
    }

    // 对话ID
    public static function chatId() {
        return Util::randStr(25);
    }

    public static function gid() {
        return Util::randStr(27);
    }
}