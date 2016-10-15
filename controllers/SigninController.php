<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Horn\Util;
use Horn\Staff;
use Horn\CaptachType;
use Horn\Exception;
use Horn\WrongArgException;
use Horn\MissingArgException;
use Horn\NeedTipException;

class SigninController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function signin($req, $rsp, $args) {
        $captcha = $req->getParam("captcha");
        if(!Util::checkCaptcha(CaptachType::SIGNIN, $captcha)) {
            throw new WrongArgException("验证码错误");
        }

        $email = $req->getParam("email");
        $pass = $req->getParam("pass");
        
        if($email == '') {
            throw new MissingArgException("请输入邮箱");
        }
        if($pass == '') {
            throw new MissingArgException("请输入密码");
        }

        $user = $this->ci->staff->auth(['email'=>$email, 'pass'=>$pass]);
        if(!$user) {
            throw new NeedTipException("账号或密码错误");
        }

        $_SESSION['user'] = $user;
        return $rsp->withJson(Util::BeJson('登录成功', 0));
    }
}