<?php
namespace Horn;

use GuzzleHttp;

class IdGen {
    public static function next() {
        $url = "http://127.0.0.1:8888/next";
        $client = new GuzzleHttp\Client();
        $res = $client->request("GET", $url);
        if($res->getStatusCode() == 200) {
            return $res->getBody()->getContents();
        }
        return false;
    }
}