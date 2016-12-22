<?php
namespace Horn;

class TagException extends Exception
{

    public function __construct($msg)
    {
        parent::__construct($msg, 500);
    }
    
}