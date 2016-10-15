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

class SignupController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    // 发送激活邮件
    public function signup_email($req, $rsp, $args) {
        $captcha = $req->getParam("captcha");
        if(!Util::checkCaptcha(CaptachType::SIGNUP_EMAIL, $captcha)) {
            throw new WrongArgException("验证码错误");
        }

        $email = $req->getParam("email");
        if($email == '') {
            throw new MissingArgException("请输入邮箱");
        }

        $staff = $this->ci->staff->findByEmail($email);
        if(!$staff) {
            throw new NeedTipException("该邮箱没有注册");
        }

        switch($staff['status']) {
            case Staff::ACTIVE:
                throw new NeedTipException("该邮箱已经激活");
            break;
            case Staff::INACTIVE:
                throw new NeedTipException("该邮箱暂时无法使用");
            break;
        }

        $token = $this->ci->staff->genActiveToken($email);

        $this->ci->mail->push($email, "signup", array("token" => $token));

        return $rsp->withJson(Util::BeJson("邮件已发出", 0));
    }

    // 确认邮件
    public function confirm($req, $rsp, $args) {
        $s = $req->getParam("s");
        return $rsp->getBody()->write($s);
    }

    // 注册
    public function signup($req, $rsp, $args) {
        $this->ci->logger->info("signup");
        $captcha = $req->getParam("captcha");
        if(!Util::checkCaptcha(CaptachType::SIGNUP, $captcha)) {
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

        $staff = $this->ci->staff->findByEmail($email);
        if($staff) {
            switch($staff['status']) {
                case Staff::PENDING:
                    $token = $this->ci->staff->genActiveToken($email);
                    $this->ci->mail->push($email, "signup", array("token" => $token));
                    throw new NeedTipException("重新发送了确认邮件");
                break;
                case Staff::ACTIVE:
                    throw new NeedTipException("该邮箱已经被注册");
                break;
                case Staff::INACTIVE:
                    throw new NeedTipException("该邮箱暂时无法使用");
                break;
            }
        }

        $this->ci->staff->create(0, ['email' => $email, 'pass' => $pass]);

        $_SESSION['signup_email'] = $email;

        $token = $this->ci->staff->genActiveToken($email);
        $this->ci->mail->push($email, "signup", array("token" => $token));
        return $rsp->withJson(Util::BeJson('注册成功', 0));
    }
}