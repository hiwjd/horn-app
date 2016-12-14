<?php
namespace Horn;

use Psr\Log\LoggerInterface;

class Chat {

    private $logger;
    private $queue;
    private $db;

    public function __construct(LoggerInterface $logger, Queue $queue, Db $db) {
        $this->logger = $logger;
        $this->queue = $queue;
        $this->db = $db;
    }

    public function dispatchMsg($body, $ip, $addr) {
        $this->logger->info("Chat.dispatchMsg: $body");
        $arr = json_decode($body, true);
        $this->logger->info(" -> ".var_export($arr, true));

        if(!is_array($arr)) {
            throw new WrongArgException("消息格式无法解析");
        }

        if(!isset($arr["oid"])) {
            throw new WrongArgException("消息缺少组织ID");
        }
        $arr["oid"] = intval($arr["oid"]);

        if(isset($arr["from"])) {
            $arr["from"]["oid"] = intval($arr["from"]["oid"]);
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
                if(!isset($arr["file"])) {
                    throw new WrongArgException("缺少[file]");
                }
                break;

            case 'image':
                if(!isset($arr["image"])) {
                    throw new WrongArgException("缺少[image]");
                }
                break;

            case 'request_chat':
                if(!isset($arr["event"])) {
                    throw new WrongArgException("缺少[event]");
                }
                if(!isset($arr["chat"])) {
                    throw new WrongArgException("缺少[event.chat]");
                }
                $arr["event"]["chat"]["cid"] = IdGen::chatId();
                break;

            case 'join_chat':
                if(!isset($arr["event"])) {
                    throw new WrongArgException("缺少[event]");
                }
                if(!isset($arr["event"]["cid"])) {
                    throw new WrongArgException("缺少[event.cid]");
                }
                break;

            case 'track':
                if(!isset($arr["event"])) {
                    throw new WrongArgException("缺少[event]");
                }
                if(!isset($arr["event"]["cid"])) {
                    throw new WrongArgException("缺少[event.cid]");
                }
                break;
            
            default:
                throw new WrongArgException("不支持消息[$type]");
                break;
        }

        $arr["mid"] = IdGen::next(); // 先生成消息ID
        $arr["t"] = array("t0" => time());

        $payload = self::getPrefix($type).json_encode($arr, JSON_UNESCAPED_UNICODE);
        $this->queue->push(Queue::TOPIC_MESSAGE, $payload);

        return $arr;
    }

    public function getMessages($cond) {
        $limit = $cond["limit"];
        $arr = array($cond["chatId"]);
        $where = "";
        if($cond["mid"]) {
            if($cond["direction"] == "next") {
                $where = " and mid > ? ";
            } else {
                $where = " and mid < ? ";
            }
            $arr[] = $cond["mid"];
        }
        $sql = "select * from messages where cid = ? $where limit $limit";
        $rows = $this->db->GetRows($sql, $arr);
        $tot = $this->db->GetNum("select count(1) from messages where cid = ? $where", $arr);

        return array(
            "data" => $rows,
            "tot" => $tot
        );
    }

    // 推给nsq的消息前面加个前缀，方便消费者在解析json之前就知道是什么类型
    private static function getPrefix($type) {
        $map = array(
            'text' => '#a',
            'file' => '#b',
            'image' => '#c',
            'request_chat' => '#d',
            'join_chat' => '#e'
        );
        return isset($map[$type]) ? $map[$type] : '';
    }
}