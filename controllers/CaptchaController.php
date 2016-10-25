<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\CaptachType;

class CaptchaController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function signup(Request $req, Response $rsp, $args) {
        Util::captcha(CaptachType::SIGNUP);

        return $rsp->withHeader('Content-type', 'image/jpeg');
    }

    public function signin(Request $req, Response $rsp, $args) {
        Util::captcha(CaptachType::SIGNIN);

        return $rsp->withHeader('Content-type', 'image/jpeg');
    }

    public function signup_email(Request $req, Response $rsp, $args) {
        Util::captcha(CaptachType::SIGNUP_EMAIL);

        return $rsp->withHeader('Content-type', 'image/jpeg');
    }
}