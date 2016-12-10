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

class SignupController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    // 发送激活邮件
    public function signup_email(Request $req, Response $rsp, $args) {
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

        $this->ci->mail->push($email, "signup", "");

        return $rsp->withJson(Util::BeJson("邮件已发出", 0));
    }

    // 确认邮件
    public function confirm(Request $req, Response $rsp, $args) {
        $token = $req->getParam("s");
        $email = $this->ci->staff->confirmEmail($token);

        return $rsp->withRedirect("/email_active?email=$email");
    }

    // 注册
    public function signup(Request $req, Response $rsp, $args) {
        $this->ci->logger->info("signup");
        $captcha = $req->getParam("captcha");
        if(!Util::checkCaptcha(CaptachType::SIGNUP, $captcha)) {
            throw new WrongArgException("验证码错误");
        }

        $comName = $req->getParam("com_name");
        $email = $req->getParam("email");
        $pass = $req->getParam("pass");
        
        if($comName == '') {
            throw new MissingArgException("请输入公司／团队名称");
        }
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
                    $this->ci->mail->push($email, "signup", "");
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

        $org = $this->ci->org->findByName($comName);
        if($org) {
            throw new NeedTipException("该公司／团队已经被注册，需要加入该公司／团队，请联系负责人开通帐号");
        }

        $oid = $this->ci->org->create($comName);

        $uid = $this->ci->staff->create($oid, ['email' => $email, 'pass' => $pass]);

        $_SESSION['signup_email'] = $email;

        $this->ci->mail->push($email, Mail::SIGNUP, "");
        return $rsp->withJson(Util::BeJson('注册成功', 0));
    }
}