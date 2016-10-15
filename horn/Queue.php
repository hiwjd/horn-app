<?php
namespace Horn;

use GuzzleHttp;

class Queue {

    const TOPIC_SIGNUP_EMAIL = 'signup_email';

    private $logger;
    private $client;
    private $host;

    public function __construct($logger, $host) {
        $this->logger = $logger;
        $this->client = new GuzzleHttp\Client();
        $this->host = $host;
    }

    public function push($topic, $payload) {
        $this->logger->info("Queue.push topic[$topic] payload[$payload]");
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
        $this->logger->info(" -> body[$body]");

        return $body;
    }

}