<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Predis\Client;

class TestCommand extends ConsoleKit\Command
{

    public function execute(array $args, array $options = array())
    {
        // $logger = new Logger('test-command');
        // $logger->pushHandler(new StreamHandler('test-command.log', Logger::DEBUG));

        // $dsn = 'mysql:host=localhost;port=4000;dbname=test';
        // $user = 'root';
        // $pass = '';

        // $db = new Horn\Db($logger, $dsn, $user, $pass);
        // $rows = $db->GetRows("select * from staff");
        // var_dump($rows);
        
        $redis = new Predis\Client();
        //$ret = $redis->zadd("pushers1", 0, "http://127.0.0.1:9001/push");
        //var_dump($ret);
        // $ret = $redis->zincrby("pushers1", 1, "http://127.0.0.1:9002/push");
        // var_dump($ret);

        $ret = $redis->zrange("pushers1", 0, 0);
        var_dump($ret);
    }

}