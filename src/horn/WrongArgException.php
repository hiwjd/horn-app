<?php
namespace Horn;

class WrongArgException extends Exception {

    public function __construct($msg) {
        parent::__construct($msg, 4);
    }
}