<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\Staff;
use Horn\CaptachType;
use Horn\Exception;
use Horn\WrongArgException;
use Horn\MissingArgException;
use Horn\NeedTipException;
use Horn\Mail;

class FindPassController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function request(Request $req, Response $rsp, $args) {
        $captcha = $req->getParam("captcha");
        if(!Util::checkCaptcha(CaptachType::FIND_PASS, $captcha)) {
            throw new WrongArgException("验证码错误");
        }

        $email = $req->getParam("email");
        
        if($email == '') {
            throw new MissingArgException("请输入邮箱");
        }

        $staff = $this->ci->staff->findByEmail($email);
        if(!$staff) {
            throw new NeedTipException("没有找到这个帐号");
        }

        $this->ci->mail->push($email, Mail::FIND_PASS, "");
        return $rsp->withJson(Util::BeJson('发送了重置密码的链接到邮箱', 0));
    }

    public function showReset(Request $req, Response $rsp, $args) {
        $token = $req->getParam("s");

        try {
            $this->ci->staff->getFindPassByToken($token);
        } catch(Exception $e) {
            return $rsp->withRedirect("/error/reset_pass?msg=".$e->getMessage());
        }

        return $rsp->withRedirect("/reset_pass?token=$token");
    }

    public function reset(Request $req, Response $rsp, $args) {
        $token = $req->getParam("token");
        $pass = $req->getParam("pass");
        $repass = $req->getParam("repass");

        $token = trim($token);
        $pass = trim($pass);

        if($token == "") {
            throw new WrongArgException("非法请求");
        }

        if($pass == "") {
            throw new WrongArgException("请输入密码");
        }

        if($pass != $repass) {
            throw new WrongArgException("请确认两次密码是否一致");
        }

        $this->ci->staff->resetPass($token, $pass);

        return $rsp->withJson(Util::BeJson('密码修改成功', 0));
    }
}