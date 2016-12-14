<?php
namespace Horn;

use GuzzleHttp;
use Psr\Log\LoggerInterface;

class Queue {

    const TOPIC_SEND_EMAIL = 'send_email';
    const TOPIC_MESSAGE = 'message';
    const TOPIC_TRACK = 'track';
    const TOPIC_TIMEOUT = 'timeout';

    private $logger;
    private $client;
    private $host;

    public function __construct(LoggerInterface $logger, $host) {
        $this->logger = $logger;
        $this->client = new GuzzleHttp\Client();
        $this->host = $host;
    }

    public function push($topic, $payload) {
        $this->logger->info("Queue.push topic[$topic] payload[".var_export($payload, true)."]");
        $options = array(
            "query" => array(
                "topic" => $topic
            ),
            "body" => $payload
        );
        $url = $this->host."/pub";
        $this->logger->info(" -> url[$url] options ", $options);

        $res = $this->client->request("POST", $url, $options);
        $body = $res->getBody()->getContents();
        $this->logger->info(" -> rsp[$body]");

        return $body;
    }

}