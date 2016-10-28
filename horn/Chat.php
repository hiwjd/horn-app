<?php
namespace Horn;

use Psr\Log\LoggerInterface;

class Chat {

    private $logger;
    private $queue;

    public function __construct(LoggerInterface $logger, Queue $queue) {
        $this->logger = $logger;
        $this->queue = $queue;
    }

    public function dispatchMsg($body) {
        $this->logger->info("Chat.dispatchMsg: $body");
        $arr = json_decode($body, true);
        $this->logger->info(" -> ".var_export($arr, true));

        if(!is_array($arr)) {
            throw new WrongArgException("消息格式无法解析");
        }

        if(!isset($arr["type"])) {
            throw new WrongArgException("消息缺少类型");
        }
        $type = $arr["type"];

        switch ($type) {
            case 'text':
                if(!isset($arr["text"])) {
                    throw new WrongArgException("缺少[text]");
                }
                break;

            case 'file':
                # code...
                break;

            case 'image':
                # code...
                break;

            case 'event':
                # code...
                break;
            
            default:
                # code...
                break;
        }

        $arr["mid"] = IdGen::next(); // 先生成消息ID
        $arr["t"] = array("t0" => time());

        $payload = self::getPrefix($type).json_encode($arr, JSON_UNESCAPED_UNICODE);
        $this->queue->push(Queue::TOPIC_MESSAGE, $payload);

        return $arr;
    }

    // 推给nsq的消息前面加个前缀，方便消费者在解析json之前就知道是什么类型
    private static function getPrefix($type) {
        $map = array(
            'text' => '#a',
            'file' => '#b',
            'image' => '#c',
            'event' => '#d'
        );
        return isset($map[$type]) ? $map[$type] : '';
    }
}