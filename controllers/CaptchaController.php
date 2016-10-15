<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Horn\Util;
use Horn\CaptachType;

class CaptchaController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function signup($req, $rsp, $args) {
        Util::captcha(CaptachType::SIGNUP);

        return $rsp->withHeader('Content-type', 'image/jpeg');
    }

    public function signin($req, $rsp, $args) {
        Util::captcha(CaptachType::SIGNIN);

        return $rsp->withHeader('Content-type', 'image/jpeg');
    }

    public function signup_email($req, $rsp, $args) {
        Util::captcha(CaptachType::SIGNUP_EMAIL);

        return $rsp->withHeader('Content-type', 'image/jpeg');
    }
}