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
        $chats = $this->db->GetRows("select c.*,c.chat_id as id from chat_user cu left join chats c on cu.chat_id=c.chat_id where cu.uid=?", array($uid));
        //$chats = $this->redis->smembers("user-chats-$uid");
        $version = $this->redis->get("event-version-$uid");

        return array(
            "chats" => $chats,
            "version" => $version
        );
    }

    public function mustGetUid($fp) {
        $uid = $this->getUidByFP($fp);
        if(!$uid) {
            $uid = Util::randStr(23);
            $this->setUidByFP($fp, $uid);
        }

        return $uid;
    }

    public function manageOnlineUsers($uid) {
        return $this->redis->sadd("user-online-ids", $uid);
    }

    public function getOnlineUsers() {
        $uids = $this->redis->smembers("user-online-ids");
        ;
    }

    public function staffSignin($staff) {
        $cid = $staff["cid"];
        return $this->redis->sadd("company-staffs-$cid", $staff["id"]);
    }

    public function checkTimeout($uid, $interval) {
        $key = "timeout-$uid";
        $latest = $this->redis->get($key);
        if(!$latest) {
            $this->redis->set($key, time());
            return true;
        }

        if((time() - $latest) > $interval) {
            return false;
        }

        return true;
    }
}