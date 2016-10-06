<?php
namespace Horn;

use Gregwar\Captcha\CaptchaBuilder;
use Hashids\Hashids;

class Util {

    public static function captcha($type) {
        $builder = new CaptchaBuilder;
        $builder->build();
        $_SESSION['captcha'][$type] = $builder->getPhrase();
        $builder->output();
    }

    public static function checkCaptcha($type, $input) {
        return $_SESSION['captcha'][$type] == $input;
    }

    public static function genClienCode($id) {
        $hashids = new Hashids('horn', 10);
        return $hashids->encode($id);
    }

    public static function BeJson($error, $code) {
        return ['code' => $code, 'error' => $error];
    }
}