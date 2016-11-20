<?php
namespace Horn;

class ExpiresSignupEmailException extends Exception {
    public function __construct($msg) {
        parent::__construct($msg, 6);
    }
}