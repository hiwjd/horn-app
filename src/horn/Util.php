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

    public static function BeJson($msg, $code) {
        return ['code' => $code, 'msg' => $msg];
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

    public static function formatChat(&$chat) {
        ;
    }

    public static function formatMessage(&$msg) {
        $type = $msg["type"];

        $msg["from"] = array(
            "id" => $msg["from_uid"],
            "name" => $msg["from_name"],
            "role" => $msg["from_role"]
        );
        unset($msg["from_uid"]);
        unset($msg["from_name"]);
        unset($msg["from_role"]);

        $msg["chat"] = array(
            "id" => $msg["chat_id"]
        );
        unset($msg["chat_id"]);

        switch ($type) {
            case "text":
                break;
            case "file":
                $msg["file"] = array(
                    "name" => $msg["name"],
                    "src" => $msg["src"],
                    "size" => $msg["size"]
                );
                break;
            case "image":
                $msg["image"] = array(
                    "src" => $msg["src"],
                    "width" => $msg["width"],
                    "height" => $msg["height"],
                    "size" => $msg["size"]
                );
                break;
            case "request_chat":
            case "join_chat":
                $msg["event"] = json_decode($msg["event"]);
                break;
            
            default:
                # code...
                break;
        }

        unset($msg["name"]);
        unset($msg["src"]);
        unset($msg["width"]);
        unset($msg["height"]);
        unset($msg["size"]);
    }
}