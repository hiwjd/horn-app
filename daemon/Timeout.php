<?php
require __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Horn\IdGen;
use Horn\Queue;

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('/home/horn/horn-app/logs/app-'.date('Y-m-d').'.log', Logger::DEBUG));
$queue = new Horn\Queue($logger, "http://127.0.0.1:4151");
$redis = new Predis\Client();

function checkAndNotify($logger, $queue, $redis) {
    $logger->info("checkAndNotify");

    $msgs = array();

    $t = time() - 300; // 300秒没说话了
    $arr = $redis->zrangebyscore("LMT_TIMEOUT", 0, $t);
    $logger->info(" 消息超时: ".json_encode($arr));
    
    if(count($arr) > 0) {
        $s = "[".implode(",", $arr)."]"; // $arr是json字符串的数组: array("{\"oid\":1,\"uid\":\"sid1\"}","{\"oid\":1,\"uid\":\"sid2\"}")
        $users = json_decode($s, true);

        foreach($users as $user) {
            $msgs[] = array(
                "type" => "lmt",
                "mid" => IdGen::next(),
                "oid" => intval($user["oid"]),
                "uid" => $user["uid"]
            );
        }

        array_unshift($arr, "LMT_TIMEOUT");
        call_user_func_array(array($redis, "zrem"), $arr);
    }

    $t = time() - 100; // 100秒没有心跳了
    $arr = $redis->zrangebyscore("HB_TIMEOUT", 0, $t);
    $logger->info(" 心跳超时: ".json_encode($arr));

    if(count($arr) > 0) {
        $s = "[".implode(",", $arr)."]";
        $users = json_decode($s, true);

        foreach($users as $user) {
            $msgs[] = array(
                "type" => "hb",
                "mid" => IdGen::next(),
                "oid" => intval($user["oid"]),
                "uid" => $user["uid"]
            );
        }

        array_unshift($arr, "HB_TIMEOUT");
        call_user_func_array(array($redis, "zrem"), $arr);
    }

    if(count($msgs) > 0) {
        $payload = "#g".json_encode($msgs, JSON_UNESCAPED_UNICODE);
        $ret = $queue->push(Queue::TOPIC_TIMEOUT, $payload);
        $logger->info(" 推送超时消息得到返回:".$ret);
    } else {
        $logger->info(" 无超时");
    }
}


while(true) {
    checkAndNotify($logger, $queue, $redis);
    sleep(3);
}