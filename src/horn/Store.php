<?php
namespace Horn;

use Psr\Log\LoggerInterface;
use Predis\Client;

// 状态数据
// 如果要统一状态数据的管理 可以都集中到一个服务里去，背后用codis
// 现在赶时间，先这样做
// 现在状态数据维护的地方有：
//   这里
//   dispatcher
class Store {

    private $logger;
    private $redis;
    private $db;

    public function __construct(LoggerInterface $logger, Client $redis, Db $db) {
        $this->logger = $logger;
        $this->redis = $redis;
        $this->db = $db;
    }

    // 根据用户ID获取到分配给该用户的推送服务器地址
    // 注意，地址格式是只包含域和端口，127.0.0.1:9001
    public function getPusherByUid($uid) {
        $this->logger->info("Store.getPusherByUid uid[$uid]");
        $addr = $this->redis->get("uid-pusher-addr-$uid");
        $this->logger->info(" -> addr[$addr]");
        return $addr;
    }

    // 给用户分配推送服务器
    // 返回该服务器地址，格式：127.0.0.1:9001
    public function assignIdlePusher($uid) {
        $this->logger->info("Store.assignIdlePusher uid[$uid]");

        $ret = $this->redis->zrange("pushers", 0, 0);
        $this->logger->info(" -> ".var_export($ret, true));
        if(!$ret || !$ret[0]) {
            return false;
        }
        $addr = $ret[0];

        $this->redis->zincrby("pushers", 1, $addr);
        $this->redis->set("uid-pusher-addr-$uid", $addr);
        return $addr;
    }

    // 根据用户的“指纹”查找用户ID
    // “指纹”是浏览器根据各种因素生成的一段字符串 
    public function getUidByFP($fp) {
        return $this->redis->get("fp-$fp");
    }

    public function setUidByFP($fp, $uid) {
        return $this->redis->set("fp-$fp", $uid);
    }

    // 获取用户当下的状态数据
    // 对话，
    public function getState($uid) {
        $chats = $this->db->GetRows("select c.*,c.chat_id as id from chat_user cu left join chats c on cu.chat_id=c.chat_id where cu.uid=? and c.state='active'", array($uid));
        //$chats = $this->redis->smembers("user-chats-$uid");
        $version = $this->redis->get("state-version-$uid");

        if(is_array($chats)) {
            foreach($chats as &$chat) {
                $chatId = $chat["chat_id"];

                Util::formatChat($chat);

                $msgs = $this->db->GetRows("select * from messages where chat_id = ? order by mid desc limit 30", array($chatId));
                $msgs = array_reverse($msgs);
                foreach($msgs as &$msg) {
                    Util::formatMessage($msg);
                }
                $chat["msgs"] = $msgs;
            }
        } else {
            $chats = array();
        }

        return array(
            "chats" => $chats,
            "version" => $version
        );
    }

    public function mustGetUid($fp) {
        $uid = $this->getUidByFP($fp);
        if(!$uid) {
            $uid = IdGen::uid();
            $this->setUidByFP($fp, $uid);
        }

        return $uid;
    }

    public function getOnlineUsers($cid) {
        $sql = "select * from users where cid = ? and state = 'on'";
        return $this->db->GetRows($sql, array($cid));
    }

    public function getOnlineStaff($cid) {
        $sql = "select * from staff where cid = ? and state = 'on'";
        return $this->db->GetRows($sql, array($cid));
    }

    public function staffSignin($staff) {
        $sql = "update staff set state='on' where staff_id=?";
        return $this->db->Exec($sql, array($staff["staff_id"]));
    }

    /**
     * 检查心跳超时
     * @param  [type] $uid       用户ID
     * @param  [type] $interval  心跳间隔 多久心跳一次
     * @param  [type] $tolerance 容差 允许连续超时几次 这个次数内的不会被视为超时
     * @return [type]            [description]
     */
    public function checkTimeout($uid, $interval, $tolerance) {
        $key = "timeout-$uid"; // 最后次心跳时间
        $keyTolerance = "timeout-tolerance-$uid"; // 连续超时计次器

        $latest = $this->redis->get($key);
        // 更新最后心跳时间
        $this->redis->set($key, time());

        if(!$latest) {
            $this->redis->set($keyTolerance, 0);
            return true;
        }

        if((time() - $latest) > $interval) {
            $timeouts = $this->redis->incr($keyTolerance);
            if($timeouts > $tolerance) {
                return false;
            }
            return true; // 这里必须return 不然后面会把连续超时计次器重置成0的
        }

        // 重置连续超时计次器 正常后就需要把连续超时重置成0
        $this->redis->set($keyTolerance, 0);

        return true;
    }
}