<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Gregwar\Captcha\CaptchaBuilder;
use Horn\Util;
use Horn\CaptachType;
use Horn\Session;
use Controller\CaptchaController;
use Middleware\LoggerMiddleware;
use Middleware\CorsMiddleware;

$config = [
    "displayErrorDetails" => true
];
$app = new \Slim\App(["settings" => $config]);
$container = $app->getContainer();

// 跨域头
$app->add(new CorsMiddleware($container));

// 日志中间件
$app->add(new LoggerMiddleware($container));

$checkProxyHeaders = true;
//$trustedProxies = ['10.0.0.1', '10.0.0.2'];
$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders));

$app->get("/api", function(Request $req, Response $rsp, $args) use ($app) {
    return $rsp->withJson(array("name" => "HORN API", "version" => "1.0.0"));
});

$app->any("/api/ttt", "Controller\TestController:abc");


/******************************************************************************/
/******************************************************************************/
/****************************** 官网注册和登录等接口 ******************************/
/******************************************************************************/
/******************************************************************************/

// 注册
$app->post("/api/signup", "Controller\SignupController:signup");

// 确认注册邮箱
$app->get("/api/signup_confirm", "Controller\SignupController:confirm");

// 发送注册邮件
$app->post("/api/signup_email", "Controller\SignupController:signup_email");

// 登录
$app->post("/api/signin", "Controller\SigninController:signin");

// 登出
$app->get("/api/signout", "Controller\SignoutController:signout");

// 登录的图片验证码
$app->get("/api/captcha/signin", "Controller\CaptchaController:signin");

// 注册的图片验证码
$app->get("/api/captcha/signup", "Controller\CaptchaController:signup");

// 登录状态检查
$app->get("/api/state/check", "Controller\StateController:check");


/******************************************************************************/
/******************************************************************************/
/********************************* 通讯及其他接口 ********************************/
/******************************************************************************/
/******************************************************************************/

// 消息上发
$app->post("/api/message", "Controller\ChatController:message");

// 获取
$app->get("/api/messages", "Controller\ChatController:messages");

// 上报追踪信息
$app->get("/api/user/track", "Controller\TrackController:track");

// 客服信息
$app->get("/api/staff/info", "Controller\StaffController:info");

// 通信初始化
$app->get("/api/state/init", "Controller\StateController:init");

// 识别用户身份
$app->get("/api/user/id", "Controller\IdentityController:identity");

// 在线用户列表
$app->get("/api/user/online", "Controller\UserController:online");

// 分配推送服务
$app->get("/api/pusher/assign", "Controller\PusherController:assign");

// 心跳
$app->get("/api/ping", "Controller\PingController:ping");


/******************************************************************************/
/******************************************************************************/
/*********************************** 注册依赖 ***********************************/
/******************************************************************************/
/******************************************************************************/

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
    $dsn = 'mysql:host=localhost;dbname=horn';
    $user = 'root';
    $pass = 'rootMM123!@#';
    return new Horn\Db($c->logger, $dsn, $user, $pass);
};

// mysql读
$container['db2'] = function(ContainerInterface $c) {
    $dsn = 'mysql:host=localhost;dbname=horn';
    $user = 'root';
    $pass = 'rootMM123!@#';
    return new Horn\Db($c->logger, $dsn, $user, $pass);
};

// 客服类
$container['staff'] = function(ContainerInterface $c) {
    $staff = new Horn\Staff($c->db);
    return $staff;
};

// nsq队列操作类
$container['queue'] = function(ContainerInterface $c) {
    $queue = new Horn\Queue($c->logger, "http://127.0.0.1:4151");
    return $queue;
};

// 邮件类
$container['mail'] = function(ContainerInterface $c) {
    $mail = new Horn\Mail($c->logger, $c->queue);
    return $mail;
};

// 对话类
$container['chat'] = function(ContainerInterface $c) {
    $mail = new Horn\Chat($c->logger, $c->queue, $c->db2);
    return $mail;
};

// redis类
$container['redis'] = function(ContainerInterface $c) {
    return new Predis\Client();
};

// 状态存取
$container['store'] = function(ContainerInterface $c) {
    return new Horn\Store($c->logger, $c->redis, $c->db);
};

// 远程调用类
$container['rpc'] = function(ContainerInterface $c) {
    return new Horn\Rpc($c->logger);
};

$app->run();