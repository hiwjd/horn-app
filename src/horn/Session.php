<?php
namespace Horn;

class Session {
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return $_SESSION[$key];
    }

    public static function del($key) {
        unset($_SESSION[$key]);
    }

    public static function user() {
        return $_SESSION['user'];
    }

    public static function isSignin() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    public static function signout() {
        unset($_SESSION['user']);
    }
}