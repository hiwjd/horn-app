<?php
namespace Horn;

use Gregwar\Captcha\CaptchaBuilder;
use Hashids\Hashids;

class Util {

    public static function captcha($type, $width=150, $height=40) {
        $builder = new CaptchaBuilder;
        $builder->build($width, $height);
        $_SESSION['captcha'][$type] = $builder->getPhrase();
        $builder->output();
    }

    public static function checkCaptcha($type, $input) {
        global $container;
        $container->logger->info("xxxxx");
        if(!isset($_SESSION['captcha'])) {
            return false;
        }
        if(!isset($_SESSION['captcha'][$type])) {
            return false;
        }

        $saved = $_SESSION['captcha'][$type];
        unset($_SESSION['captcha'][$type]);
        return $saved == $input;
    }

    public static function genClienCode($id) {
        $hashids = new Hashids('horn', 10);
        return $hashids->encode($id);
    }

    public static function BeJson($error, $code) {
        return ['code' => $code, 'msg' => $error];
    }

    public static function parseEmailHost($email) {
        $arr = explode("@", $email);
        return "http://".$arr[1];
    }

    public static function randStr($length = 10) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = strlen($chars);
        $rand = '';
        for ($i = 0; $i < $length; $i++) {
            $rand .= $chars[rand(0, $len - 1)];
        }
        return $rand;
    }
}