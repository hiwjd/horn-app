<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

use Klein\Klein;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Gregwar\Captcha\CaptchaBuilder;
use Horn\Util;
use Horn\CaptachType;
use Horn\Session;


$klein = new Klein();

$klein->respond(function ($request, $response, $service, $app) use ($klein) {

    // Handle exceptions => flash the message and redirect to the referrer
    $klein->onError(function ($klein, $err_msg, $type, $err) {

        if($type == 'Horn\Exception') {
            // 可以对外输出
            $code = $err->getCode() ?: '10000';
            $res = array(
                "code" => $code,
                "error" => $err_msg
            );
        } else {
            $res = array(
                "code" => "20000",
                "error" => "出错了:".$err_msg
            );
        }

        $klein->response()->json($res);
    });

    $app->register('logger', function() {
        $logger = new Logger('www');
        $logger->pushHandler(new StreamHandler('./logs/www-'.date('Y-m-d').'.log', Logger::WARNING));
        return $logger;
    });

    // $app also can store lazy services, e.g. if you don't want to
    // instantiate a database connection on every response
    $app->register('db', function() use ($app) {
        $dsn = 'mysql:host=localhost;dbname=horn_admin';
        $user = 'root';
        $pass = '';
        return new Horn\Db($app->logger, $dsn, $user, $pass);
    });

    $app->register('redis', function() {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        return $redis;
    });

    $app->register('pusher', function() use ($app) {
        return new Horn\Pusher($app->redis);
    });

    $app->register('queue', function() {
        $m = new Memcache();
        $m->addServer("tcp://127.0.0.1:22133");
        return $m;
    });

    $app->register('client', function() use ($app) {
        $client = new Horn\Client($app->db);
        return $client;
    });

    $app->register('staff', function() use ($app) {
        $staff = new Horn\Staff($app->db);
        return $staff;
    });

    $service->layout("views/layout.php");
});

$klein->respond('GET', '/abc', function ($request, $response, $service, $app) {
    echo "abc";
});

$klein->respond('GET', '/', function ($request, $response, $service, $app) {
    $service->render("views/home.php");
});

$klein->respond('GET', '/signup', function ($request, $response, $service, $app) use ($klein) {
    if(Session::isSignin()) {
        $response->redirect('/portal');
        $klein->abort();
    }

    $service->render("views/signup.php");
});

$klein->respond('POST', '/signup', function ($request, $response, $service, $app) use ($klein) {
    $req = $request->paramsPost();
    $captcha = $req->get('captcha');
    if(!Util::checkCaptcha(CaptachType::SIGNUP, $captcha)) {
        //$response->json(Util::BeJson("sss", 1));
        //$klein->abort();
        throw new Horn\Exception("验证码错误", 1);
    }

    $name = $req->get('name');
    $email = $req->get('email');
    $pwd = $req->get('pwd');
    $tel = $req->get('tel');
    
    if($name == '') {
        //$service->flash("请输入企业名称","error");
        throw new Horn\Exception("姓名不能为空", 1);
    }
    if($email == '') {
        //$service->flash("请输入登录邮箱","error");
        throw new Horn\Exception("请输入登录邮箱", 1);
    }
    if($pwd == '') {
        //$service->flash("请输入密码","error");
        throw new Horn\Exception("请输入密码", 1);
    }
    if($tel == '') {
        //$service->flash("请输入联系电话","error");
        throw new Horn\Exception("请输入联系电话", 1);
    }

    $client = $app->client->findByName($name);
    if($client) {
        throw new Horn\Exception("该邮公司/团队名称已经被注册,您可以申请加入", 1);
    }

    $user = $app->staff->findByEmail($email);
    if($user) {
        // $service->flash("该邮箱已经被注册", "error");
        // $klein->abort();
        throw new Horn\Exception("该邮箱已经被注册", 1);
    }

    $clientId = $app->client->create(['name' => $name]);
    if(!$clientId) {
        //$service->flash("注册失败", "error");
        throw new Horn\Exception("注册失败", 1);
    }

    $app->staff->create($clientId, ['email' => $email, 'passwd' => $pwd, 'tel' => $tel]);

    //$response->redirect("/signup");
    $response->json(Util::BeJson('注册成功', 1));
});

$klein->respond('GET', '/captcha/signin', function($request, $response, $service, $app) {
    $response->header('Content-type', 'image/jpeg');
    
    Util::captcha(CaptachType::SIGNIN);
});

$klein->respond('GET', '/captcha/signup', function($request, $response, $service, $app) {
    $response->header('Content-type', 'image/jpeg');
    
    Util::captcha(CaptachType::SIGNUP);
});

$klein->respond('GET', '/signin', function($request, $response, $service, $app) {
    if(Session::isSignin()) {
        $response->redirect('/portal');
    } else {
        $service->render("views/signin.php");
    }
});

$klein->respond('POST', '/signin', function($request, $response, $service, $app) {
    $req = $request->paramsPost();
    $captcha = $req->get('captcha');
    if(!Util::checkCaptcha(CaptachType::SIGNIN, $captcha)) {
        //$response->json(Util::BeJson("sss", 1));
        //$klein->abort();
        throw new Horn\Exception("验证码错误", 1);
    }

    $email = $req->get('email');
    $pwd = $req->get('pwd');
    
    if($email == '') {
        //$service->flash("请输入登录邮箱","error");
        throw new Horn\Exception("请输入登录邮箱", 1);
    }
    if($pwd == '') {
        //$service->flash("请输入密码","error");
        throw new Horn\Exception("请输入密码", 1);
    }

    $user = $app->staff->auth(['email'=>$email, 'pwd'=>$pwd]);
    if(!$user) {
        throw new Horn\Exception("账号或密码错误", 1);
    }

    $_SESSION['user'] = $user;
    $response->json(Util::BeJson('登录成功', 1));
});

$klein->respond('GET', '/portal', function($request, $response, $service, $app) use ($klein) {
    if(!Session::isSignin()) {
        $response->redirect('/signin');
        $klein->abort();
    }

    $service->render('views/portal.php');
});

$klein->respond('GET', '/signout', function($request, $response, $service, $app) use ($klein) {
    if(Session::isSignin()) {
        Session::signout();
    }

    $response->redirect('/signin');
    $klein->abort();
});

$klein->dispatch();