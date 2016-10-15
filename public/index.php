<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
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

$app->add(new LoggerMiddleware($container));

$app->get("/api", function($req, $rsp, $args) use ($app) {
    return $rsp->withJson(array("name" => "HORN API", "version" => "1.0.0"));
});

$app->post("/api/signup", "Controller\SignupController:signup");
$app->get("/api/signup", "Controller\SignupController:confirm");
$app->post("/api/signup_email", "Controller\SignupController:signup_email");
$app->post("/api/signin", "Controller\SigninController:signin");
$app->get("/api/signout", "Controller\SignoutController:signout");
$app->get("/api/captcha/signin", "Controller\CaptchaController:signin");
$app->get("/api/captcha/signup", "Controller\CaptchaController:signup");
$app->get("/api/state/check", "Controller\StateController:check");

//$app->get("/api/state/check", "Controller\StateController:check");


$container['logger'] = function($c) {
    $logger = new Logger('app');
    $logger->pushHandler(new StreamHandler('/home/horn/horn-app/logs/app-'.date('Y-m-d').'.log', Logger::DEBUG));
    return $logger;
};
$container['errorHandler'] = function ($c) {
    return function ($req, $rsp, $e) use ($c) {
        $c->logger->error($e, array($req, $rsp));
        if($e instanceof Horn\Exception) {
        //if(get_class($e) == "Horn\Exception") {
            $arr = array(
                "code" => $e->getCode(),
                "msg" => $e->getMessage()
            );
            return $rsp->withJson($arr);
        } else {
            return $rsp->withStatus(500)
                ->withHeader('Content-Type', 'text/html')
                ->write('Something went wrong! '.$e->getMessage());
        }
    };
};
$container['db'] = function($c) {
    $dsn = 'mysql:host=localhost;dbname=horn_admin';
    $user = 'root';
    $pass = 'rootMM123!@#';
    return new Horn\Db($c->logger, $dsn, $user, $pass);
};
$container['staff'] = function($c) {
    $staff = new Horn\Staff($c->db);
    return $staff;
};
$container['queue'] = function($c) {
    $queue = new Horn\Queue($c->logger, "http://127.0.0.1:4151");
    return $queue;
};
$container['mail'] = function($c) {
    $mail = new Horn\Mail($c->logger, $c->queue);
    return $mail;
};

$app->run();