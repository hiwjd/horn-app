<?php
namespace Registry;

use Slim\App;
use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Horn;

class Dependency {

    public static function register(App $app) {
        $container = $app->getContainer();

        // 配置
        $container['config'] = function(ContainerInterface $c) {
            return parse_ini_file('/home/horn/horn-app/app.ini', true);
        };

        // 日志
        $container['logger'] = function(ContainerInterface $c) {
            $logger = new Logger('app');
            $logger->pushHandler(new StreamHandler('/home/horn/horn-app/logs/app-'.date('Y-m-d').'.log', Logger::DEBUG));
            return $logger;
        };

        // 错误处理
        $container['errorHandler'] = function (ContainerInterface $c) {
            return function (Request $req, Response $rsp, $e) use ($c) {
                $c->logger->error($e, array($req, $rsp));
                if($e instanceof Horn\Exception) {
                    $arr = array(
                        "code" => $e->getCode(),
                        "msg" => $e->getMessage()
                    );
                    return $rsp->withJson($arr);
                } else {
                    return $rsp->withStatus(500)
                        ->withHeader('Content-Type', 'text/html')
                        ->write('Something went wrong! '.get_class($e)."#".$e->getMessage()."\r\n".$e->getTraceAsString());
                }
            };
        };

        // 访问不存在的资源
        $container['notFoundHandler'] = function (ContainerInterface $c) {
            return function (Request $req, Response $rsp) use ( $c) {
                return $rsp->withJson(array(
                    "code" => 1,
                    "msg" => "api not found"
                ));
            };
        };

        // 访问不允许的资源
        $container['notAllowedHandler'] = function (ContainerInterface $c) {
            return function (Request $req, Response $rsp, $methods) use ( $c) {
                return $rsp->withJson(array(
                    "code" => 1,
                    "msg" => "method not allowed"
                ));
            };
        };

        // mysql读写
        $container['db'] = function(ContainerInterface $c) {
            $dsn = $c->config["mysql"]["dsn"];
            $user = $c->config["mysql"]["user"];
            $pass = $c->config["mysql"]["pass"];
            return new Horn\Db($c->logger, $dsn, $user, $pass);
        };

        // mysql读
        $container['db2'] = function(ContainerInterface $c) {
            $dsn = $c->config["mysql"]["dsn"];
            $user = $c->config["mysql"]["user"];
            $pass = $c->config["mysql"]["pass"];
            return new Horn\Db($c->logger, $dsn, $user, $pass);
        };

        // 客服类
        $container['staff'] = function(ContainerInterface $c) {
            $staff = new Horn\Staff($c->db, $c->mail);
            return $staff;
        };

        // 公司类
        $container['org'] = function(ContainerInterface $c) {
            $org = new Horn\Org($c->db);
            return $org;
        };

        // nsq队列操作类
        $container['queue'] = function(ContainerInterface $c) {
            $addr = $c->config["nsq"]["nsqdhttp"];
            $queue = new Horn\Queue($c->logger, $addr);
            return $queue;
        };

        // 邮件类
        $container['mail'] = function(ContainerInterface $c) {
            $mail = new Horn\Mail($c->logger, $c->queue);
            return $mail;
        };

        // 对话类
        $container['chat'] = function(ContainerInterface $c) {
            $mail = new Horn\Chat($c->logger, $c->queue, $c->db2, $c->redis);
            return $mail;
        };

        // redis类
        $container['redis'] = function(ContainerInterface $c) {
            return new \Predis\Client();
        };

        // 状态存取
        $container['store'] = function(ContainerInterface $c) {
            return new Horn\Store($c->logger, $c->redis, $c->db, "");
        };

        // 远程调用类
        $container['rpc'] = function(ContainerInterface $c) {
            return new Horn\Rpc($c->logger);
        };

        // 访客标签 
        $container['tag'] = function(ContainerInterface $c) {
            return new Horn\Tag($c->logger, $c->db);
        };

        // 访客
        $container['visitor'] = function(ContainerInterface $c) {
            return new Horn\Visitor($c->logger, $c->db);
        };

        // 客服分组
        $container['group'] = function(ContainerInterface $c) {
            return new Horn\Group($c->logger, $c->db);
        };
    }

}