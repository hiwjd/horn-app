<?php
namespace Horn;

class Pusher {
    private $redis;

    public function __construct($redis) {
        $this->redis = $redis;
    }

    public function get($uid) {
        $key = 'u-p-'.$uid;
        if($this->redis->exists($key)) {
            return $this->redis->get($key);
        }

        $keyp = 'pushers';
        if($this->redis->zSize($keyp) < 1) {
            throw new Exception('不存在下发服务器', '30000');
        }

        $addr = $this->redis->zRange($keyp, 0, 0)[0];
        $this->redis->zIncrBy($keyp, 1, $addr);

        $this->redis->set($key, $addr);

        return $addr;
    }

    public function add($addr) {
        $keyp = 'pushers';
        $pushes = $this->redis->zRange($keyp, 0, -1);

        if(in_array($addr, $pushes)) {
            return;
        }
        
        $this->redis->zAdd($keyp, 0, $addr);
    }
}