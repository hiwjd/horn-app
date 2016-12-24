<?php
namespace Registry;

use Slim\App;
use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Middleware\LoginCheckMiddleware;

class Router {

    public static function register(App $app) {
        $app->get("/api", function(Request $req, Response $rsp, $args) use ($app) {
            return $rsp->withJson(array("name" => "HORN API", "version" => "1.0.0"));
        });

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

        // 找回密码的图片验证码
        $app->get("/api/captcha/find_pass", "Controller\CaptchaController:find_pass");

        // 登录状态检查
        $app->get("/api/state/check", "Controller\StateController:check");

        // 找回密码请求
        $app->post("/api/find_pass", "Controller\FindPassController:request");

        // 找回密码
        $app->get("/api/find_pass/reset", "Controller\FindPassController:showReset");

        // 找回密码处理
        $app->post("/api/reset_pass", "Controller\FindPassController:reset");


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

        // 在线客服列表
        $app->get("/api/staff/online", "Controller\StaffController:online");

        // 心跳
        $app->get("/api/heartbeat", "Controller\HeartbeatController:heartbeat");

        // 客服 且 需要登录 的接口
        $app->group("/api/b", function() use ($app) {
            // 在线用户列表
            $app->get("/users/online", "Controller\UserController:online");

            // 访客访问轨迹
            $app->get("/user/tracks", "Controller\UserController:tracks");

            // 获取历史对话
            $app->get("/history/chats", "Controller\HistoryController:chats");

            // 获取某个对话的详细数据
            $app->get("/history/chat", "Controller\HistoryController:chat");

            // 获取访客标签
            $app->get("/visitor/tags", "Controller\TagController:getByVisitor");

            // 添加标签
            $app->get("/tags", "Controller\TagController:get");

            // 保存标签 新增／修改
            $app->post("/tag/save", "Controller\TagController:save");

            // 添加标签
            $app->post("/tag/add", "Controller\TagController:add");

            // 编辑标签
            $app->post("/tag/edit", "Controller\TagController:edit");

            // 删除标签
            $app->post("/tag/delete", "Controller\TagController:delete");

            // 贴标签
            $app->post("/tag/attach", "Controller\TagController:attach");

            // 撕标签
            $app->post("/tag/detach", "Controller\TagController:detach");

            // 添加访客信息
            $app->get("/visitor/info/add", "Controller\VisitorInfoController:add");

            // 编辑访客信息
            $app->get("/visitor/info/edit", "Controller\VisitorInfoController:edit");

            // 删除访客信息
            $app->get("/visitor/info/remove", "Controller\VisitorInfoController:remove");

            // 客服列表
            $app->get("/staffs", "Controller\StaffController:get");

            // 保存客服 新增／修改
            $app->post("/staff/save", "Controller\StaffController:save");

            // 修改密码
            $app->post("/staff/editpwd", "Controller\StaffController:editpwd");

            // 修改访客信息
            $app->post("/visitor/edit", "Controller\VisitorController:edit");

            // 获取客服分组列表
            $app->get("/groups", "Controller\GroupController:get");

            // 获取客服分组列表
            $app->post("/group/save", "Controller\GroupController:save");
        })->add(new LoginCheckMiddleware());
    }

}