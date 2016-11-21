<?php
namespace Registry;

use Slim\App;
use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Router {

    public static function register(App $app) {
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
        $app->get("/api/users/online", "Controller\UserController:online");

        // 在线客服列表
        $app->get("/api/staff/online", "Controller\StaffController:online");

        // 心跳
        $app->get("/api/heartbeat", "Controller\HeartbeatController:heartbeat");
    }

}