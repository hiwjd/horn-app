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
        $url = "http://".$addr."/join";
        $payload = json_encode(array("uid" => $uid));
        $this->logger->info(" -> url[$url] payload[$payload]");
        $options = array(
            "body" => $payload
        );

        try {
            $res = $this->client->request("POST", $url, $options);
        } catch(GuzzleHttp\Exception\BadResponseException $e) {
            $this->logger->error(" -> BadResponseException err: ".$e->getMessage());
            $res = $e->getResponse();
        } catch(GuzzleHttp\Exception\ConnectException $e) {
            $this->logger->error(" -> ConnectException err: ".$e->getMessage());
            return false;
        }
        
        $body = $res->getBody()->getContents();
        $this->logger->info(" -> body[$body]");

        $ret = json_decode($body, true);
        if($ret["code"] != 0) {
            $this->logger->error(" -> 失败, 返回: $body");
            return false;
        } else {
            return true;
        }
    }

}