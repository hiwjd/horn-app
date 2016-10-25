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

    public function __construct(LoggerInterface $logger, Client $redis) {
        $this->logger = $logger;
        $this->redis = $redis;
    }

    public function getPusherByUid($uid) {
        $this->logger->info("Store.getPusherByUid uid[$uid]");
        $addr = $this->redis->get("uid-pusher-addr-$uid");
        $this->logger->info(" -> addr[$addr]");
        return $addr;
    }

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

    public function getUidByFP($fp) {
        return $this->redis->get("fp-$fp");
    }

    public function setUidByFP($fp, $uid) {
        return $this->redis->set("fp-$fp", $uid);
    }
}