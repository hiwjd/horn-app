<?php
namespace Horn;

class NeedTipException extends Exception {

    public function __construct($msg) {
        parent::__construct($msg, 5);
    }
}