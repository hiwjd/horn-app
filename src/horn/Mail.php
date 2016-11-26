<?php
namespace Horn;

use Psr\Log\LoggerInterface;

class Mail {

    const SIGNUP = 'signup';
    const FIND_PASS = 'find_pass';

    private $logger;
    private $queue;

    public function __construct(LoggerInterface $logger, $queue) {
        $this->logger = $logger;
        $this->queue = $queue;
    }

    public function push($email, $type, $data) {
        $this->logger->info("Mail.push email[$email] type[$type] data[".var_export($data, true)."]");
        $payload = json_encode(array(
            "email" => $email,
            "type" => $type,
            "data" => $data
        ), JSON_UNESCAPED_UNICODE);

        $this->logger->info(" -> payload: [$payload]");

        $res = $this->queue->push(Queue::TOPIC_SEND_EMAIL, "#g".$payload);

    }

}