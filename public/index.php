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

$config = [
    "displayErrorDetails" => true
];
$app = new \Slim\App(["settings" => $config]);
$container = $app->getContainer();

$app->add(function(Request $req, Response $rsp, callable $next) {
    $newrsp = $rsp->withHeader("Access-Control-Allow-Origin", "*");
    return $next($req, $newrsp);
});

// 日志中间件
$app->add(new LoggerMiddleware($container));

$checkProxyHeaders = true;
//$trustedProxies = ['10.0.0.1', '10.0.0.2'];
$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders));

$app->get("/api", function(Request $req, Response $rsp, $args) use ($app) {
    return $rsp->withJson(array("name" => "HORN API", "version" => "1.0.0"));
});

$app->any("/api/ttt", "Controller\TestController:abc");

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

// 消息上发
$app->post("/api/message", "Controller\ChatController:message");

// 上报追踪信息
$app->get("/api/user/track", "Controller\TrackController:track");

// 识别用户身份
$app->get("/api/user/id", "Controller\IdentityController:identity");

$container['logger'] = function(ContainerInterface $c) {
    $logger = new Logger('app');
    $logger->pushHandler(new StreamHandler('/home/horn/horn-app/logs/app-'.date('Y-m-d').'.log', Logger::DEBUG));
    return $logger;
};
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
$container['notFoundHandler'] = function (ContainerInterface $c) {
    return function (Request $req, Response $rsp) use ( $c) {
        return $rsp->withJson(array(
            "code" => 1,
            "msg" => "api not found"
        ));
    };
};
$container['notAllowedHandler'] = function (ContainerInterface $c) {
    return function (Request $req, Response $rsp, $methods) use ( $c) {
        return $rsp->withJson(array(
            "code" => 1,
            "msg" => "method not allowed"
        ));
    };
};
$container['db'] = function(ContainerInterface $c) {
    $dsn = 'mysql:host=localhost;dbname=horn_admin';
    $user = 'root';
    $pass = 'rootMM123!@#';
    return new Horn\Db($c->logger, $dsn, $user, $pass);
};
$container['staff'] = function(ContainerInterface $c) {
    $staff = new Horn\Staff($c->db);
    return $staff;
};
$container['queue'] = function(ContainerInterface $c) {
    $queue = new Horn\Queue($c->logger, "http://127.0.0.1:4151");
    return $queue;
};
$container['mail'] = function(ContainerInterface $c) {
    $mail = new Horn\Mail($c->logger, $c->queue);
    return $mail;
};
$container['chat'] = function(ContainerInterface $c) {
    $mail = new Horn\Chat($c->logger, $c->queue);
    return $mail;
};
$container['redis'] = function(ContainerInterface $c) {
    return new Predis\Client();
};
$container['store'] = function(ContainerInterface $c) {
    return new Horn\Store($c->logger, $c->redis);
};
$container['rpc'] = function(ContainerInterface $c) {
    return new Horn\Rpc($c->logger);
};

$app->run();