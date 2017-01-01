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

        $arr["created_at"] = date("c");

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
                if(!isset($arr["event"]["chat"])) {
                    throw new WrongArgException("缺少[event.chat]");
                }
                if(!isset($arr["event"]["chat"]["sid"])) {
                    throw new WrongArgException("缺少[event.chat.sid]");
                }

                // 如果sid以#开头，说明是分组ID
                // 从分组中分配一个可以接待对话的客服
                $sid = $arr["event"]["chat"]["sid"];
                if(substr($sid, 0, 1) == "#") {
                    $gid = substr($sid, 1);
                    $oid = $arr["event"]["chat"]["oid"];
                    $sid = $this->GetAvaiableSidByGid($oid, $gid);
                    if(!$sid) {
                        throw new Exception("暂时没有能接待的客服", 303);
                    }
                    $arr["event"]["chat"]["sid"] = $sid;
                }

                $arr["event"]["chat"]["oid"] = intval($arr["event"]["chat"]["oid"]);
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

    public function getChatList($cond) {
        $oid = $cond["oid"];
        $page = $cond["page"];
        $size = $cond["size"];
        $offset = ($page-1)*$size;
        //$sql = "select c.*,s.name as staff_name from chats c left join staff s on c.sid=s.sid where c.oid = ? order by created_at desc limit $offset,$size";
        $sql = "select c.*,s.name as staff_name,v.name as visitor_name from chats c left join staff s on c.sid=s.sid and c.oid=s.oid left join visitors v on c.vid=v.vid and c.oid=v.oid where c.oid = ? order by c.created_at desc limit $offset,$size";
        $rows = $this->db->GetRows($sql, array($oid));
        $total = $this->db->GetNum("select count(1) from chats where oid = ?", array($oid));

        return array(
            "data" => $rows,
            "total" => $total
        );
    }

    public function getChat($oid, $cid) {
        //$chats = $this->db->GetRow("select c.*,c.cid as id from chat_user cu left join chats c on cu.cid=c.cid where cu.oid=? and cu.uid=? and c.state='active'", array($oid, $uid));
        $chat = $this->db->GetRow("select c.*,c.cid as id from chats c where c.oid = ? and c.cid = ?", array($oid, $cid));

        $chatId = $chat["cid"];

        self::formatChat($this->db, $chat);

        $msgs = $this->db->GetRows("select * from messages where cid = ? order by mid desc limit 30", array($chatId));
        if(!is_array($msgs)){
            $msgs=array();
        }
        $msgs = array_reverse($msgs);
        foreach($msgs as &$msg) {
            self::formatMessage($msg);
        }
        $chat["msgs"] = $msgs;

        $tracks = self::getTracks($this->db, $chat["oid"], $chat["vid"]);
        if(!$tracks) {
            $tracks = array();
        }
        $chat["tracks"] = $tracks;

        $tags = self::getTags($this->db, $chat["oid"], $chat["vid"]);
        if(!$tags) {
            $tags = array();
        }
        $chat["tags"] = $tags;

        return $chat;
    }

    public static function formatChat(Db $db, &$chat) {
        $oid = $chat["oid"];
        $vid = $chat["vid"];
        $sid = $chat["sid"];

        $visitor = $db->GetRow("select * from visitors where oid = ? and vid = ?", array($oid, $vid));
        $chat["visitor"] = $visitor;

        $staff = $db->GetRow("select * from staff where oid = ? and sid = ?", array($oid, $sid));
        $chat["staff"] = $staff;

        $tracks = $db->GetRows("select * from tracks where oid = ? and vid = ? order by created_at desc limit 5", array($oid, $vid));
        if(!is_array($tracks)) {
            $tracks = array();
        }
        $chat['tracks'] = $tracks;
    }

    public static function formatMessage(&$msg) {
        $type = $msg["type"];

        $msg["from"] = array(
            "uid" => $msg["from_uid"],
            "name" => $msg["from_name"],
            "role" => $msg["from_role"]
        );
        unset($msg["from_uid"]);
        unset($msg["from_name"]);
        unset($msg["from_role"]);

        switch ($type) {
            case "text":
                unset($msg["event"]);
                break;
            case "file":
                $msg["file"] = array(
                    "name" => $msg["name"],
                    "src" => $msg["src"],
                    "size" => $msg["size"]
                );
                break;
            case "image":
                $msg["image"] = array(
                    "src" => $msg["src"],
                    "width" => $msg["width"],
                    "height" => $msg["height"],
                    "size" => $msg["size"]
                );
                break;
            case "request_chat":
            case "join_chat":
                $msg["event"] = json_decode($msg["event"]);
                break;
            
            default:
                # code...
                break;
        }

        unset($msg["name"]);
        unset($msg["src"]);
        unset($msg["width"]);
        unset($msg["height"]);
        unset($msg["size"]);
    }

    public static function getTracks(Db $db, $oid, $vid) {
        $sql = "select * from tracks where oid = ? and vid = ? order by created_at desc limit 5";
        return $db->GetRows($sql, array($oid, $vid));
    }

    public static function getTags(Db $db, $oid, $vid) {
        $sql = "select t.* from visitor_tags vt left join tags t on vt.tag_id=t.id where vt.oid = ? and vt.vid = ?";
        return $db->GetRows($sql, array($oid, $vid));
    }

    private function GetAvaiableSidByGid($oid, $gid) {
        $sql = "select sid from staff where oid = ? and state = 'on' and gid = ? and ccn > ccn_cur order by updated_at asc limit 1";
        return $this->db->GetStr($sql, array($oid, $gid));
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