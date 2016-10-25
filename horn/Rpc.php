<?php
namespace Horn;

use GuzzleHttp;
use Psr\Log\LoggerInterface;

class Rpc {

    private $logger;
    private $client;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->client = new GuzzleHttp\Client();
    }

    public function joinPusher($addr, $uid) {
        $this->logger->info("Rpc.joinPusher addr[$addr] uid[$uid]");
        $url = $addr."/join";
        $payload = json_encode(array("uid" => $uid));
        $this->logger->info(" -> url[$url] payload[$payload]");
        $options = array(
            "body" => $payload
        );
        $res = $this->client->request("POST", $url, $options);
        $body = $res->getBody()->getContents();
        $this->logger->info(" -> body[$body]");

        $ret = json_decode($body, true);
        if($ret["code"] != 0) {
            $this->logger->error("Rpc.joinPusher 失败, 返回: $body");
            return false;
        } else {
            return true;
        }
    }

}