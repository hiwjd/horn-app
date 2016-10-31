<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Predis\Client;

class TestRedisCommand extends ConsoleKit\Command
{

    public function execute(array $args, array $options = array())
    {
        $redis = new Predis\Client();
        $ret = $redis->smembers("user-chats-1");
        var_dump($ret);

        $redis->sadd("user-chats-1", "chat1");

        $ret = $redis->smembers("user-chats-1");
        var_dump($ret);
    }

}