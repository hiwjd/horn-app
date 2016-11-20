<?php
namespace Horn;

class MissingArgException extends Exception {

    public function __construct($msg) {
        parent::__construct($msg, 3);
    }
}